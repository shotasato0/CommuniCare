<template>
  <div class="unit-sidebar">
    <ul>
      <li v-for="unit in units" :key="unit.id">
        {{ unit.name }}
      </li>
    </ul>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      units: [],
    };
  },
  mounted() {
    this.fetchUnits();
  },
  methods: {
    fetchUnits() {
      axios.get('/api/units')
        .then(response => {
          this.units = response.data;
        })
        .catch(error => {
          console.error("There was an error fetching the units: ", error);
        });
    }
  }
}
</script>

<style scoped>
.unit-sidebar ul {
  list-style-type: none;
  padding: 0;
}

.unit-sidebar li {
  margin-bottom: 10px;
}
</style>
