<template>
  <k-view class="kirbystats-view">
    <k-header>
      Stats for /{{ id }}
      <date-shortcuts @select="period = $event" />
    </k-header> 
    <date-range class="k-section" v-model="period"/>
    <stats-chart :logs="logs" :period="period" resolution="daily" />
  </k-view>
</template>

<script>
import DateRange from './DateRange.vue'
import DateShortcuts from './DateShortcuts.vue'
import StatsChart from './StatsChart.vue'

export default {
  components: {
    DateRange,
    DateShortcuts,
    StatsChart
  },

  data() {
    return {
      id: 'home',
      logs: {},
      period: {
        from: null,
        to: null
      }
    }
  },

  watch: {
    period() {
      this.updateStats()
    }
  },

  methods: {
    updateStats() {
      const id = 'home'
      const { from, to } = this.period
      this.$api.get(`stats/${id}/${from}/${to}/daily`).then(data => {
        this.logs = data.data
      })
    }
  }
}
</script>