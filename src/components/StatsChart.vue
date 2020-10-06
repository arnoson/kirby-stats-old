<template>
  <div class="kirbystats-chart" ref="chart" @mousemove="onMouseMove" />
</template>

<script>
import Chartist from 'chartist'
import 'chartist/dist/chartist.css'

export default {
  props: {
    logs: {
      type: Object,
      default: () => ({ daily: [], hourly: [] })
    },
    resolution: {
      type: String,
      default: 'hourly'
    },
    period: {
      type: Object,
      default: () => ({ from: null, to: null })
    }
  },

  computed: {
    interval() {
      return this.resolution === 'hourly' ? 3600 : 3600 * 24
    },

    data() {
      const fromTime = new Date(this.period.from).getTime() / 1000
      const toTime = new Date(this.period.to).getTime() / 1000
      const { hourly, daily } = this.logs

      const labels = []
      const views = []
      const visits = []

      const { interval } = this


      for (let time = fromTime; time < toTime; time += interval) {
        labels.push(new Date(time * 1000).getHours())
        views.push(hourly?.[time]?.views ?? daily?.[time]?.views ?? 0)
        visits.push(hourly?.[time]?.visits ?? daily?.[time]?.visits ?? 0)
      }

      return {
        labels,
        series: [
          { value: views, className: 'ct-series-views' },
          { value: visits, className: 'ct-series-visits' }
        ]
      }
    }
  },

  watch: {
    data(value) {
      this.chart?.update(value)
    }
  },

  mounted() {
    this.chart = new Chartist.Line(this.$refs.chart, this.data, {
      height: '100%',
      lineSmooth: Chartist.Interpolation.none({ fillHoles: true }),
      showArea: true,
      axisX:{
        // labelOffset: {
        //   x: -10,
        //   y: 0
        // }          
      },
      chartPadding: {
        top: 10,
        right: 20,
        bottom: 0,
        left: 0
      },
      fullWidth: true
    }, [
      ['screen and (max-width: 640px)', {
        height: '40%',
        axisX: {
          labelInterpolationFnc: function(value, index) {
            return index === 0 || index % 5 === 4 ? value : null;
          }
        },
      }]
    ]);
  },

  methods: {
    onMouseMove({ target }) {
      if (target.classList.contains('ct-point')) {
        console.log(target.getAttribute('ct:value'))
      }
    }
  }
}
</script>

<style lang="scss">
.kirbystats-chart {
  height: 50vh;

  .ct-line {
    stroke-width: 2px;
  }

  .ct-point {
    stroke-width: 20px;
    stroke: transparent;
    &:hover {
      stroke: inherit;
      stroke-width: 8px;
    }
  }

  .ct-series-views,
  .ct-series-visits {
    stroke: #4271ae;
    fill: #4271ae;

    .ct-area {
      fill-opacity: 0.2;
    }
  }

}
</style>