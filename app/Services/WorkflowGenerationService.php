<?php

namespace App\Services;

use App\Models\AiIntent;
use App\Models\PipelineError;
use App\Models\Recording;
use App\Models\Workflow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkflowGenerationService
{
    /**
     * Intent action to n8n node type mapping.
     */
    protected const NODE_MAP = [
        'http_request' => 'n8n-nodes-base.httpRequest',
        'send_email' => 'n8n-nodes-base.gmail',
        'spreadsheet_read' => 'n8n-nodes-base.googleSheets',
        'spreadsheet_write' => 'n8n-nodes-base.googleSheets',
        'database_query' => 'n8n-nodes-base.postgres',
        'send_slack_message' => 'n8n-nodes-base.slack',
        'webhook_trigger' => 'n8n-nodes-base.webhook',
        'code_execute' => 'n8n-nodes-base.code',
        'if_condition' => 'n8n-nodes-base.if',
        'set_variable' => 'n8n-nodes-base.set',
    ];

    protected const VARIANTS = ['minimal', 'robust', 'with_logging'];

    /**
     * Generate n8n workflows from an AI intent.
     * Creates multiple variants: minimal, robust, with_logging.
     * Updates recording status: generating_workflows → workflows_ready (or failed).
     */
    public function generate(Recording $recording, AiIntent $intent): void
    {
        $recording->update(['status' => 'generating_workflows']);

        try {
            $currentVersion = Workflow::where('ai_intent_id', $intent->id)->max('version') ?? 0;
            $newVersion = $currentVersion + 1;

            foreach (self::VARIANTS as $variant) {
                $workflowJson = $this->generateVariant($intent, $variant);

                Workflow::create([
                    'recording_id' => $recording->id,
                    'ai_intent_id' => $intent->id,
                    'organization_id' => $recording->organization_id,
                    'engine' => 'n8n',
                    'variant' => $variant,
                    'version' => $newVersion,
                    'workflow_json' => $workflowJson,
                    'node_count' => count($workflowJson['nodes'] ?? []),
                    'status' => 'generated',
                ]);
            }

            $recording->update(['status' => 'workflows_ready']);
        } catch (\Throwable $e) {
            $recording->update(['status' => 'failed']);

            PipelineError::create([
                'recording_id' => $recording->id,
                'stage' => 'workflow_generation',
                'error_code' => 'WORKFLOW_GENERATION_FAILED',
                'error_message' => $e->getMessage(),
                'error_context' => ['trace' => substr($e->getTraceAsString(), 0, 2000)],
                'created_at' => now(),
            ]);

            Log::error('Workflow generation failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a specific variant of n8n workflow JSON.
     */
    protected function generateVariant(AiIntent $intent, string $variant): array
    {
        $nodes = [];
        $connections = [];

        // Start trigger node
        $startNode = [
            'id' => (string) Str::uuid(),
            'name' => 'Start',
            'type' => 'n8n-nodes-base.manualTrigger',
            'position' => [250, 300],
            'parameters' => (object) [],
        ];
        $nodes[] = $startNode;

        $steps = $intent->steps ?? [];
        $previousNodeName = 'Start';

        foreach ($steps as $index => $step) {
            $node = $this->mapStepToNode($step, $index);
            $nodes[] = $node;

            $connections[$previousNodeName] = [
                'main' => [[
                    ['node' => $node['name'], 'type' => 'main', 'index' => 0],
                ]],
            ];

            $previousNodeName = $node['name'];
        }

        if ($variant === 'robust') {
            $this->addErrorHandling($nodes, $connections);
        }

        if ($variant === 'with_logging') {
            $this->addLogging($nodes, $connections);
        }

        return $this->buildWorkflowJson($nodes, $connections, $variant, $intent);
    }

    /**
     * Map an intent step to an n8n node definition.
     */
    protected function mapStepToNode(array $step, int $index): array
    {
        $action = $step['action'] ?? 'unknown';
        $nodeType = self::NODE_MAP[$action] ?? 'n8n-nodes-base.noOp';

        $parameters = $step['parameters'] ?? [];

        // Add note for unmapped actions
        if (!isset(self::NODE_MAP[$action])) {
            $parameters['note'] = "Unmapped action: {$action}. Please configure manually.";
        }

        // Map specific parameters based on action type
        $parameters = $this->mapActionParameters($action, $parameters);

        $xPos = 250 + (($index + 1) * 200);

        return [
            'id' => (string) Str::uuid(),
            'name' => $this->generateNodeName($step, $index),
            'type' => $nodeType,
            'position' => [$xPos, 300],
            'parameters' => empty($parameters) ? (object) [] : $parameters,
        ];
    }

    /**
     * Map action-specific parameters to n8n node parameters.
     */
    protected function mapActionParameters(string $action, array $parameters): array
    {
        return match ($action) {
            'http_request' => array_merge([
                'method' => $parameters['method'] ?? 'GET',
                'url' => $parameters['url'] ?? '',
            ], $parameters),
            'send_email' => array_merge([
                'sendTo' => $parameters['to'] ?? '',
                'subject' => $parameters['subject'] ?? '',
                'message' => $parameters['body'] ?? '',
            ], $parameters),
            'spreadsheet_read', 'spreadsheet_write' => array_merge([
                'operation' => $action === 'spreadsheet_read' ? 'read' : 'append',
                'sheetName' => $parameters['sheet'] ?? 'Sheet1',
            ], $parameters),
            'database_query' => array_merge([
                'query' => $parameters['query'] ?? '',
            ], $parameters),
            'send_slack_message' => array_merge([
                'channel' => $parameters['channel'] ?? '',
                'text' => $parameters['message'] ?? '',
            ], $parameters),
            default => $parameters,
        };
    }

    /**
     * Generate a descriptive node name.
     */
    protected function generateNodeName(array $step, int $index): string
    {
        $app = $step['app'] ?? 'Step';
        $action = $step['action'] ?? 'action';
        $name = "{$app} - " . str_replace('_', ' ', ucfirst($action));

        return $name;
    }

    /**
     * Build n8n workflow JSON structure with nodes and connections.
     */
    protected function buildWorkflowJson(array $nodes, array $connections, string $variant, AiIntent $intent = null): array
    {
        $title = $intent ? $intent->title : 'Untitled Workflow';

        return [
            'name' => "FlowCast: {$title} ({$variant})",
            'nodes' => $nodes,
            'connections' => $connections,
            'settings' => [
                'executionOrder' => 'v1',
            ],
        ];
    }

    /**
     * Add error handling nodes for 'robust' variant.
     */
    protected function addErrorHandling(array &$nodes, array &$connections): void
    {
        $lastNode = end($nodes);
        $lastNodeName = $lastNode['name'];
        $lastPos = $lastNode['position'];

        $errorNode = [
            'id' => (string) Str::uuid(),
            'name' => 'Error Handler',
            'type' => 'n8n-nodes-base.errorTrigger',
            'position' => [$lastPos[0], $lastPos[1] + 200],
            'parameters' => (object) [],
        ];

        $stopNode = [
            'id' => (string) Str::uuid(),
            'name' => 'Stop and Error',
            'type' => 'n8n-nodes-base.stopAndError',
            'position' => [$lastPos[0] + 200, $lastPos[1] + 200],
            'parameters' => [
                'errorMessage' => 'Workflow execution failed. Check the error details.',
            ],
        ];

        $nodes[] = $errorNode;
        $nodes[] = $stopNode;

        $connections['Error Handler'] = [
            'main' => [[
                ['node' => 'Stop and Error', 'type' => 'main', 'index' => 0],
            ]],
        ];
    }

    /**
     * Add logging nodes for 'with_logging' variant.
     */
    protected function addLogging(array &$nodes, array &$connections): void
    {
        $lastNode = end($nodes);
        $lastNodeName = $lastNode['name'];
        $lastPos = $lastNode['position'];

        $logNode = [
            'id' => (string) Str::uuid(),
            'name' => 'Log Result',
            'type' => 'n8n-nodes-base.set',
            'position' => [$lastPos[0] + 200, $lastPos[1]],
            'parameters' => [
                'values' => [
                    'string' => [
                        [
                            'name' => 'log_timestamp',
                            'value' => '={{ $now.toISO() }}',
                        ],
                        [
                            'name' => 'log_status',
                            'value' => 'completed',
                        ],
                    ],
                ],
            ],
        ];

        $nodes[] = $logNode;

        $connections[$lastNodeName] = [
            'main' => [[
                ['node' => 'Log Result', 'type' => 'main', 'index' => 0],
            ]],
        ];
    }
}
