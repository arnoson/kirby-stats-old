<template>
  <k-view class="k-moviereviews-view">
    <k-header>Stats</k-header>
    <div ref="chart" class="chart"></div>
  </k-view>
</template>

<script>
import Chartist from 'chartist'
import 'chartist/dist/chartist.css'

var data = {
  // A labels array that can contain any sort of values
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
  // Our series array that contains series objects or in this case series data arrays
  series: [
    [5, 2, 4, 2, 0, 5, 2, 4, 2, 0, 5, 2, 4, 2, 0, 5, 2, 4, 2, 0, 5, 2, 4, 2, 0, 5, 2, 4, 2, 0]
  ]
};

export default {
  data() {
    return {
      test: {}
    }
  },

  created() {
    this.load()
  },

  mounted() {
    new Chartist.Line(this.$refs.chart, data, {
      height: '30vw',
      lineSmooth: Chartist.Interpolation.none(),
      axisX:{
        // offset: 0,
        labelOffset: {
          x: -10,
          y: 0
        },        
      },
      axisY:{
        // offset: 30              
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
        height: '40vh',
        axisX: {
            labelInterpolationFnc: function(value, index) {
              return index === 0 || index % 5 === 4 ? value : null;
            }
        },
      }]
    ]);
  },

  methods: {
    load() {
      this.$api.get('stats').then(data => {
        this.test = data
      })
    }
  }
}
</script>

<style lang="scss">
.chart {
  .ct-line {
    stroke-width: 2px;
  }

  .ct-point {
    stroke-width: 6px;
  }
}
</style>
