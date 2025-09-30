export const useDashboard = () => {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  // Dashboard statistics
  const stats = ref({
    users: { total: 0, growth: 0 },
    promotions: { total: 0, growth: 0 },
    rewards: { total: 0, growth: 0 },
    revenue: { total: 0, growth: 0 }
  })

  // Chart data
  const chartData = ref({
    promotion_trends: {
      labels: [],
      datasets: []
    },
    user_activity: {
      labels: [],
      datasets: []
    },
    reward_distribution: {
      labels: [],
      datasets: []
    }
  })

  // Recent activities
  const recentActivities = ref([])

  // Top performers
  const topPerformers = ref([])

  /**
   * Fetch dashboard overview
   */
  const fetchDashboardOverview = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.getDashboardStats({
        period: '30 days',
        ...params
      })

      if (response.status === 'success') {
        const data = response.data.dashboard

        // Update stats
        stats.value = {
          users: {
            total: data.users?.total || 0,
            growth: data.users?.growth_percentage || 0
          },
          promotions: {
            total: data.promotions?.total || 0,
            growth: data.promotions?.growth_percentage || 0
          },
          rewards: {
            total: data.rewards?.total_distributed || 0,
            growth: data.rewards?.growth_percentage || 0
          },
          revenue: {
            total: data.revenue?.total || 0,
            growth: data.revenue?.growth_percentage || 0
          }
        }

        // Update recent activities
        recentActivities.value = data.recent_activities || []

        // Update top performers
        topPerformers.value = data.top_performers || []
      }
    } catch (err) {
      console.error('Failed to fetch dashboard overview:', err)
      error.value = 'Failed to load dashboard data'
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch promotion trends for charts
   */
  const fetchPromotionTrends = async (params = {}) => {
    try {
      const response = await api.getPromotionStats({
        period: 'daily',
        limit: 30,
        ...params
      })

      if (response.status === 'success') {
        const stats = response.data.statistics

        // Process promotion trends
        const labels = stats.map(item => {
          const date = new Date(item.date)
          return date.toLocaleDateString('zh-TW', { month: 'short', day: 'numeric' })
        })

        chartData.value.promotion_trends = {
          labels,
          datasets: [
            {
              label: '點擊數',
              data: stats.map(item => item.clicks || 0),
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
              fill: true
            },
            {
              label: '轉換數',
              data: stats.map(item => item.conversions || 0),
              borderColor: 'rgb(16, 185, 129)',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.4,
              fill: true
            }
          ]
        }
      }
    } catch (err) {
      console.error('Failed to fetch promotion trends:', err)
    }
  }

  /**
   * Fetch reward statistics for charts
   */
  const fetchRewardStats = async (params = {}) => {
    try {
      const response = await api.getRewardStats({
        period: '7 days',
        ...params
      })

      if (response.status === 'success') {
        const data = response.data

        // Process reward distribution
        if (data.distribution_by_type) {
          const types = Object.keys(data.distribution_by_type)
          const values = Object.values(data.distribution_by_type)

          chartData.value.reward_distribution = {
            labels: types,
            datasets: [{
              label: '獎勵發放數量',
              data: values,
              backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)'
              ],
              borderColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)'
              ],
              borderWidth: 1
            }]
          }
        }
      }
    } catch (err) {
      console.error('Failed to fetch reward stats:', err)
    }
  }

  /**
   * Format number with K, M suffix
   */
  const formatNumber = (num) => {
    if (num >= 1000000) {
      return (num / 1000000).toFixed(1) + 'M'
    }
    if (num >= 1000) {
      return (num / 1000).toFixed(1) + 'K'
    }
    return num.toString()
  }

  /**
   * Format percentage
   */
  const formatPercentage = (num) => {
    return `${num > 0 ? '+' : ''}${num.toFixed(1)}%`
  }

  /**
   * Format currency
   */
  const formatCurrency = (num) => {
    return new Intl.NumberFormat('zh-TW', {
      style: 'currency',
      currency: 'TWD'
    }).format(num)
  }

  /**
   * Get formatted stats for display
   */
  const formattedStats = computed(() => [
    {
      name: '總用戶數',
      value: formatNumber(stats.value.users.total),
      growth: formatPercentage(stats.value.users.growth),
      icon: 'UsersIcon',
      positive: stats.value.users.growth >= 0
    },
    {
      name: '推廣點擊',
      value: formatNumber(stats.value.promotions.total),
      growth: formatPercentage(stats.value.promotions.growth),
      icon: 'CursorArrowRaysIcon',
      positive: stats.value.promotions.growth >= 0
    },
    {
      name: '發放獎勵',
      value: formatNumber(stats.value.rewards.total),
      growth: formatPercentage(stats.value.rewards.growth),
      icon: 'GiftIcon',
      positive: stats.value.rewards.growth >= 0
    },
    {
      name: '平台收益',
      value: formatCurrency(stats.value.revenue.total),
      growth: formatPercentage(stats.value.revenue.growth),
      icon: 'CurrencyDollarIcon',
      positive: stats.value.revenue.growth >= 0
    }
  ])

  /**
   * Refresh all dashboard data
   */
  const refreshDashboard = async (params = {}) => {
    await Promise.all([
      fetchDashboardOverview(params),
      fetchPromotionTrends(params),
      fetchRewardStats(params)
    ])
  }

  return {
    loading,
    error,
    stats,
    chartData,
    recentActivities,
    topPerformers,
    formattedStats,
    fetchDashboardOverview,
    fetchPromotionTrends,
    fetchRewardStats,
    refreshDashboard,
    formatNumber,
    formatPercentage,
    formatCurrency
  }
}