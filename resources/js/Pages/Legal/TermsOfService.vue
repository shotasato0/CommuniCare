<script>
import Footer from "@/Layouts/Footer.vue";
import { Head } from "@inertiajs/vue3";

export default {
    name: "TermsOfService",
    components: {
        Footer,
        Head,
    },
};
</script>

<template>
    <Head :title="'利用規約'" />
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-left">利用規約</h1>

        <p class="mb-6">
            本利用規約（以下、「本規約」といいます）は、このウェブサイト上で提供するサービス「CommuniCare」（以下、「本サービス」といいます）の利用条件を定めるものです。
            本サービスの利用者（以下、「ユーザー」といいます）は、本規約に同意の上、本サービスをご利用ください。
        </p>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">第1条（適用）</h2>
        <p class="mb-4">
            本規約は、ユーザーと本サービスとの間の本サービスの利用に関わる一切の関係に適用されるものとします。
        </p>
        <p class="mb-4">
            本サービスは、本規約のほか、利用にあたってのルール等（以下、「個別規定」といいます。）を設けることがあります。
            これら個別規定は、本規約の一部を構成するものとします。
        </p>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第2条（利用登録）
        </h2>
        <p class="mb-4">
            登録希望者が本規約に同意し、本サービスの定める方法によって利用登録を申請し、本サービスがこれを承認することで、利用登録が完了します。
        </p>
        <p class="mb-4">
            本サービスは、以下の事由がある場合、利用登録を承認しないことがあります。
        </p>
        <ul class="list-disc pl-6 mb-4">
            <li>利用登録の申請に虚偽の事項が含まれる場合</li>
            <li>過去に本規約違反があった場合</li>
            <li>その他、本サービスが適切でないと判断した場合</li>
        </ul>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第3条（ユーザーIDおよびパスワードの管理）
        </h2>
        <p class="mb-4">
            ユーザーは、自己の責任において、本サービスのユーザーIDおよびパスワードを適切に管理するものとします。
            また、第三者との共有・譲渡・貸与を禁止し、不正使用による損害について本サービスは責任を負いません。
        </p>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第4条（利用料金および支払方法）
        </h2>
        <p class="mb-4">
            本サービスの基本利用は無料です。ただし、今後一部のサービスについて有料プランを導入する場合があります。その際は事前に通知し、ユーザーの同意を得るものとします。
        </p>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第5条（禁止事項）
        </h2>
        <p class="mb-4">
            ユーザーは、本サービスの利用にあたり、以下の行為を禁止します。
        </p>
        <ul class="list-disc pl-6 mb-4">
            <li>法令または公序良俗に違反する行為</li>
            <li>犯罪行為に関連する行為</li>
            <li>他のユーザーや第三者の権利を侵害する行為</li>
            <li>本サービスの運営を妨害する行為</li>
        </ul>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第6条（利用制限および登録抹消）
        </h2>
        <p class="mb-4">
            本サービスは、ユーザーが規約違反をした場合、事前通知なく本サービスの利用を制限、または登録を抹消することができます。
        </p>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第7条（介護施設での利用に関する責任）
        </h2>
        <p class="mb-4">
            本サービスは介護施設向けに提供されるものであり、施設管理者およびスタッフは以下の責任を負います。
        </p>
        <ul class="list-disc pl-6 mb-4">
            <li>
                本サービスは介護業務を支援する補助ツールであり、医療・介護判断を代替するものではありません。
            </li>
            <li>
                施設管理者は、スタッフが適切に本サービスを利用し、利用者の個人情報を適切に管理する責任を負います。
            </li>
            <li>
                施設職員は、本サービスを利用する際、利用者のデータを慎重に取り扱い、第三者への不適切な開示を防ぐものとします。
            </li>
            <li>
                施設管理者およびスタッフは、本サービスを利用する際、プライバシーポリシーに従い、利用者の個人情報を適切に取り扱うものとします。
            </li>
            <li>
                利用者のデータは、介護記録および業務遂行の目的に限定して利用し、第三者への無断開示を禁止します。
            </li>
        </ul>

        <h2 class="text-2xl font-semibold mb-4 border-b pb-2">
            第8条（準拠法・裁判管轄）
        </h2>
        <p class="mb-4">
            本規約の解釈には日本法を準拠法とし、紛争が生じた場合には本サービスの運営所在地の裁判所を専属的合意管轄とします。
        </p>
    </div>

    <Footer />
</template>

<style scoped>
.container {
    max-width: 800px;
}
</style>
