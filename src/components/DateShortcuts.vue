<template>
  <k-button-group>       
    <k-button icon="calendar" @click="select('day')">Today</k-button>
    <k-button icon="calendar" @click="select('week')">Week</k-button>
    <k-button icon="calendar" @click="select('month')">Month</k-button>
    <k-button icon="calendar" @click="select('year')">Year</k-button>
  </k-button-group>
</template>

<script>
export default {
  methods: {
    select(unit) {
      const today = new Date()
      const tomorrow = new Date(new Date().setDate(today.getDate() + 1))
      
      // To include today, we have to look at all data until tomorrow.
      const to = tomorrow
      let from

      switch (unit) {
        case 'day':
          from = today
          break
        case 'week':
          from = new Date(new Date(today).setDate(today.getDate() - 7))
          break
        case 'month':
          from = new Date(new Date(today).setMonth(today.getMonth() - 1))
          break
        case 'year':
          from = new Date(new Date(today).setFullYear(today.getFullYear() - 1))
          break
      }

      this.$emit('select', {
        from: this.format(from),
        to: this.format(to)
      })
    },

    format(date) {
      return date.toISOString().substring(0, 10)      
    }
  }
}
</script>