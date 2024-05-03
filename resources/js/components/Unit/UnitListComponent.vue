<template>
    <div>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.id"
                @click="selectUnit(unit.id)"
            >
                <!-- router-linkを使用して動的なリンクを作成 -->
                <router-link :to="`/units/${unit.id}`">
                    {{ unit.name }}
                </router-link>
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
            // この例では何もしていませんが、必要に応じて何か処理を追加できます
        },
    },
};
</script>
