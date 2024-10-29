<template>
    <div class="sidebar bg-gray-100 w-60 h-screen p-4 shadow-lg">
      <h2 class="text-xl font-bold mb-4">部署一覧</h2>
      <ul>
        <li 
          v-for="unit in units" 
          :key="unit.id" 
          class="mb-2 p-2 rounded hover:bg-gray-200 cursor-pointer">
          {{ unit.name }}
        </li>
      </ul>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      units: {
        type: Array,
        required: true,
      },
    },
  };
  </script>
  
  <style scoped>
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    background-color: #f7fafc;
  }
  </style>
  