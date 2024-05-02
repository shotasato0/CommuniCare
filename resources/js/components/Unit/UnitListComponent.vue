<template>
    <div>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.unit_id"
                @click="selectUnit(unit.unit_id)"
            >
                {{ unit.unit_name }}
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    data() {
        return {
            units: [],
        };
    },
    mounted() {
        axios.get("/api/units").then((response) => {
            this.units = response.data;
        });
    },
    methods: {
        selectUnit(unitId) {
            // 選択されたユニットに基づいて何かをする
        },
    },
};
</script>
