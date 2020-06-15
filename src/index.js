import View from './components/View.vue'

panel.plugin('arnoson/kirby-stats', {
  views: {
    stats: {
      component: View,
      icon: 'chart',
      label: 'Stats',
    },
  },
})
