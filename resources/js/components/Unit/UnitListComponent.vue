<template>
    <div>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.id"
                @click="selectUnit(unit.id)"
            >
                {{ unit.name }}
            </li>
        </ul>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            units: [],
        };
    },
    mounted() {
        const apiUrl = import.meta.env.VITE_API_URL;
        axios
            .get(`${apiUrl}/units`)
            .then((response) => {
                this.units = response.data;
            })
            .catch((error) => {
                console.error("Error fetching units:", error);
            });
    },
    methods: {
        selectUnit(unitId) {
            // 選択されたユニットに基づいて何かをする
        },
    },
};
</script>
