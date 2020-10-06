<template>
  <div class="date-range">
    <k-fieldset
      :value="value"
      @input="input"
      :fields="{
        from: {
          label: 'From',
          type: 'date',
          time: false,
          width: '1/2'
        },
        to: {
          label: 'To',
          type: 'date',
          time: false,
          width: '1/2'
        }
      }"
    />
  </div>
</template>

<script>
export default {
  props: {
    value: Object
  },

  methods: {
    input({ from, to }) {
      // Remove timestamp and emit the new dates.
      this.$emit('input', {
        from: from?.substring(0, 10),
        to: to?.substring(0, 10)
      })
    }
  }
}
</script>