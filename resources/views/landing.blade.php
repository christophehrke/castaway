<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowCast – Turn Screencasts into n8n Workflows</title>
    <meta name="description" content="FlowCast converts screen recordings with audio into executable n8n workflows using AI. Record, analyze, automate.">

    <!-- Open Graph -->
    <meta property="og:title" content="FlowCast – Turn Screencasts into n8n Workflows">
    <meta property="og:description" content="Record your screen, narrate your process, and get an automated n8n workflow in minutes.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ url('/images/og-image.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FlowCast – Turn Screencasts into n8n Workflows">
    <meta name="twitter:description" content="Record your screen, narrate your process, and get an automated n8n workflow in minutes.">

    <!-- JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "FlowCast",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "FlowCast converts screen recordings into executable n8n workflows using AI vision and speech analysis.",
        "url": "{{ url('/') }}",
        "offers": {
            "@type": "AggregateOffer",
            "lowPrice": "29",
            "highPrice": "249",
            "priceCurrency": "USD",
            "offerCount": "3"
        }
    }
    </script>

    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-gray-900 antialiased">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="/" class="text-xl font-bold text-indigo-600">FlowCast</a>
                    <div class="hidden md:flex items-center gap-6">
                        <a href="#features" class="text-sm text-gray-600 hover:text-gray-900">Features</a>
                        <a href="#how-it-works" class="text-sm text-gray-600 hover:text-gray-900">How It Works</a>
                        <a href="#pricing" class="text-sm text-gray-600 hover:text-gray-900">Pricing</a>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/login" class="text-sm text-gray-600 hover:text-gray-900">Login</a>
                    <a href="/register" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">Start Free Trial</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-gray-900">
                Turn Screencasts into n8n Workflows
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto">
                Record your screen, narrate your process, and FlowCast's AI will generate an executable n8n workflow in minutes.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/register" class="rounded-lg bg-indigo-600 px-8 py-3 text-base font-medium text-white hover:bg-indigo-700 transition">Start Free Trial</a>
                <a href="#pricing" class="rounded-lg border border-gray-300 px-8 py-3 text-base font-medium text-gray-700 hover:bg-gray-50 transition">View Pricing</a>
            </div>
        </div>
    </header>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gray-50 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900">How It Works</h2>
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold mb-6">1</div>
                    <h3 class="text-xl font-semibold text-gray-900">Record</h3>
                    <p class="mt-3 text-gray-600">Capture your screen and narrate what you're doing using our Chrome extension or upload a video.</p>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold mb-6">2</div>
                    <h3 class="text-xl font-semibold text-gray-900">Analyze</h3>
                    <p class="mt-3 text-gray-600">FlowCast's AI understands your actions through speech-to-text and visual analysis.</p>
                </div>
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 text-2xl font-bold mb-6">3</div>
                    <h3 class="text-xl font-semibold text-gray-900">Automate</h3>
                    <p class="mt-3 text-gray-600">Get executable n8n workflow JSON ready to import and run.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900">Features</h2>
            <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">AI-Powered Analysis</h3>
                    <p class="mt-2 text-gray-600">Combines speech-to-text and computer vision to understand your workflow.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">n8n Workflow Export</h3>
                    <p class="mt-2 text-gray-600">Generates valid n8n JSON with multiple variants: minimal, robust, and with logging.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Chrome Extension</h3>
                    <p class="mt-2 text-gray-600">Record directly from your browser with one click.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Pipeline Transparency</h3>
                    <p class="mt-2 text-gray-600">See exactly how your recording was analyzed: transcript, vision data, and intent model.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Team Collaboration</h3>
                    <p class="mt-2 text-gray-600">Multi-seat organizations with role-based access.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">API-First</h3>
                    <p class="mt-2 text-gray-600">Full REST API for integration with your existing tools.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-gray-50 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900">Simple, Transparent Pricing</h2>
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="rounded-xl border border-gray-200 bg-white p-8 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-900">Starter</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900">$29</span>
                        <span class="text-gray-500">/mo</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-600 flex-1">
                        <li>20 recordings</li>
                        <li>10 min max</li>
                        <li>5 GB storage</li>
                        <li>1 seat</li>
                    </ul>
                    <a href="/register" class="mt-8 block text-center rounded-lg border border-indigo-600 px-4 py-2.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition">Get Started</a>
                </div>
                <!-- Pro -->
                <div class="rounded-xl border-2 border-indigo-600 bg-white p-8 flex flex-col ring-1 ring-indigo-600 relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white">Popular</span>
                    <h3 class="text-lg font-semibold text-gray-900">Pro</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900">$79</span>
                        <span class="text-gray-500">/mo</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-600 flex-1">
                        <li>100 recordings</li>
                        <li>30 min max</li>
                        <li>50 GB storage</li>
                        <li>5 seats</li>
                    </ul>
                    <a href="/register" class="mt-8 block text-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">Get Started</a>
                </div>
                <!-- Enterprise -->
                <div class="rounded-xl border border-gray-200 bg-white p-8 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-900">Enterprise</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900">$249</span>
                        <span class="text-gray-500">/mo</span>
                    </div>
                    <ul class="mt-6 space-y-3 text-gray-600 flex-1">
                        <li>500 recordings</li>
                        <li>60 min max</li>
                        <li>500 GB storage</li>
                        <li>25 seats</li>
                    </ul>
                    <a href="/register" class="mt-8 block text-center rounded-lg border border-indigo-600 px-4 py-2.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900">Frequently Asked Questions</h2>
            <div class="mt-12 space-y-4">
                <details class="group rounded-xl border border-gray-200 p-6">
                    <summary class="flex cursor-pointer items-center justify-between font-medium text-gray-900">
                        What video formats are supported?
                        <span class="ml-4 text-gray-400 group-open:rotate-180 transition-transform">&#9662;</span>
                    </summary>
                    <p class="mt-4 text-gray-600">MP4, WebM, MOV, and AVI.</p>
                </details>
                <details class="group rounded-xl border border-gray-200 p-6">
                    <summary class="flex cursor-pointer items-center justify-between font-medium text-gray-900">
                        Do I need an n8n instance?
                        <span class="ml-4 text-gray-400 group-open:rotate-180 transition-transform">&#9662;</span>
                    </summary>
                    <p class="mt-4 text-gray-600">FlowCast generates the workflow JSON. You can import it into any n8n instance.</p>
                </details>
                <details class="group rounded-xl border border-gray-200 p-6">
                    <summary class="flex cursor-pointer items-center justify-between font-medium text-gray-900">
                        How accurate is the AI analysis?
                        <span class="ml-4 text-gray-400 group-open:rotate-180 transition-transform">&#9662;</span>
                    </summary>
                    <p class="mt-4 text-gray-600">FlowCast uses state-of-the-art AI models. Results improve with clear narration and visible UI actions.</p>
                </details>
                <details class="group rounded-xl border border-gray-200 p-6">
                    <summary class="flex cursor-pointer items-center justify-between font-medium text-gray-900">
                        Can I cancel anytime?
                        <span class="ml-4 text-gray-400 group-open:rotate-180 transition-transform">&#9662;</span>
                    </summary>
                    <p class="mt-4 text-gray-600">Yes, all plans are month-to-month with no long-term commitment.</p>
                </details>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-200 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-lg font-bold text-indigo-600">FlowCast</span>
            <div class="flex items-center gap-6 text-sm text-gray-500">
                <a href="#" class="hover:text-gray-700">Privacy Policy</a>
                <a href="#" class="hover:text-gray-700">Terms of Service</a>
                <a href="#" class="hover:text-gray-700">Contact</a>
            </div>
            <p class="text-sm text-gray-400">&copy; {{ date('Y') }} FlowCast. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
