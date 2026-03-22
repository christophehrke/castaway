import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Public routes (AuthLayout)
    {
      path: '/login',
      component: () => import('../pages/LoginPage.vue'),
      meta: { layout: 'auth', guest: true },
    },
    {
      path: '/register',
      component: () => import('../pages/RegisterPage.vue'),
      meta: { layout: 'auth', guest: true },
    },

    // App routes (AppLayout, requires auth)
    {
      path: '/app/dashboard',
      component: () => import('../pages/DashboardPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/recordings',
      component: () => import('../pages/RecordingsListPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/recordings/:id',
      component: () => import('../pages/RecordingDetailPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/workflows',
      component: () => import('../pages/WorkflowsListPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/workflows/:id',
      component: () => import('../pages/WorkflowDetailPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/api-keys',
      component: () => import('../pages/ApiKeysPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/billing',
      component: () => import('../pages/BillingPage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },
    {
      path: '/app/profile',
      component: () => import('../pages/ProfilePage.vue'),
      meta: { layout: 'app', requiresAuth: true },
    },

    // Admin routes (AdminLayout, requires superadmin)
    {
      path: '/admin/dashboard',
      component: () => import('../pages/AdminDashboardPage.vue'),
      meta: { layout: 'admin', requiresAuth: true, requiresAdmin: true },
    },

    // Redirect root to dashboard or login
    {
      path: '/',
      redirect: '/app/dashboard',
    },

    // Catch-all: redirect to dashboard
    {
      path: '/:pathMatch(.*)*',
      redirect: '/app/dashboard',
    },
  ],
})

router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore()

  // If we have a token but no user data, fetch it
  if (auth.isAuthenticated && !auth.user) {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return next('/app/dashboard')
  }

  if (to.meta.requiresAdmin && !auth.user?.is_superadmin) {
    return next('/app/dashboard')
  }

  next()
})

export default router
