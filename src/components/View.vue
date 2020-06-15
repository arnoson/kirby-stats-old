<template>
  <k-view class="k-moviereviews-view">
    <k-header>Stats</k-header>
    <div>{{ test.test }}</div>
  </k-view>
</template>

<script>
export default {
  data() {
    return {
      test: {}
    }
  },

  created() {
    this.load()
    console.log(this.$route)
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
