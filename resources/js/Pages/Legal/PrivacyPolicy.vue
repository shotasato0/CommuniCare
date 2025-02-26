<script>
import Footer from "@/Layouts/Footer.vue";

export default {
    name: "PrivacyPolicy",
    components: {
        Footer,
    },
};
</script>

<template>
    <div class="privacy-policy max-w-3xl mx-auto p-5 leading-relaxed">
        <h1 class="text-3xl font-bold mb-4">プライバシーポリシー</h1>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            1. はじめに
        </h2>
        <p class="mb-4">
            本プライバシーポリシーでは、「CommuniCare」（以下、「本サービス」といいます）が、ユーザーおよび介護サービス利用者の個人情報およびその他の情報をどのように収集、使用、管理、保護するかについて説明します。
        </p>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            2. 収集する情報
        </h2>
        <p class="mb-4">
            本サービスが収集する情報には、以下のものが含まれますが、これに限定されません：
        </p>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2">
                個人情報 (ユーザー):
                ユーザー名、メールアドレス、電話番号、プロフィール画像など
            </li>
            <li class="mb-2">
                個人情報 (介護サービス利用者): 氏名、健康状態、介護記録など
            </li>
            <li class="mb-2">
                アプリ使用に関する情報: 投稿内容、利用状況、設定など
            </li>
            <li class="mb-2">
                デバイス情報: IPアドレス、Cookie、ブラウザの種類、アクセス日時
            </li>
            <li class="mb-2">
                その他: アプリの機能提供およびサービス向上に必要な情報
            </li>
        </ul>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            3. 情報の利用目的
        </h2>
        <p class="mb-4">収集した情報は、以下の目的で使用されます：</p>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2">アプリの機能提供およびサービス運営のため</li>
            <li class="mb-2">介護サービス利用者のケア管理および記録のため</li>
            <li class="mb-2">サービスの改善や新機能の開発のため</li>
            <li class="mb-2">ユーザーサポートおよび問い合わせ対応のため</li>
            <li class="mb-2">不正利用の防止および安全性確保のため</li>
            <li class="mb-2">法令遵守および法的義務の履行のため</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            4. 情報の共有と開示
        </h2>
        <p class="mb-4">
            個人情報は、以下の場合を除き、第三者に共有または開示されません：
        </p>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2">
                ユーザーまたは介護サービス利用者の同意がある場合
            </li>
            <li class="mb-2">法的要求や裁判所命令に応じる場合</li>
            <li class="mb-2">
                アプリの運営に必要な業務委託先（クラウドサービス、サーバー運営会社等）への提供
            </li>
            <li class="mb-2">
                介護サービス提供に必要な医療機関や関係者への共有（法令に従う場合）
            </li>
        </ul>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            5. 情報の保護
        </h2>
        <p class="mb-4">
            本サービスは、収集した情報の安全性を確保するため、以下の対策を講じます：
        </p>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2">
                個人情報の不正アクセス、漏洩、改ざん、紛失を防止するための技術的対策
            </li>
            <li class="mb-2">
                個人情報へのアクセスを制限し、必要な従業員のみに許可
            </li>
            <li class="mb-2">セキュリティシステムの定期的な更新および監視</li>
            <li class="mb-2">個人情報の取り扱いに関する従業員への教育と指導</li>
        </ul>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            6. ユーザーおよび介護サービス利用者の権利
        </h2>
        <p class="mb-4">
            ユーザーおよび介護サービス利用者は、以下の権利を有します。
        </p>
        <ul class="list-disc pl-5 mb-4">
            <li class="mb-2">個人情報へのアクセス、訂正、削除の請求</li>
            <li class="mb-2">
                個人情報の利用停止および第三者への提供の停止の請求
            </li>
            <li class="mb-2">Cookieの無効化（ブラウザの設定で変更可能）</li>
        </ul>
        <p class="mb-4">
            これらの権利を行使する場合は、以下のXアカウントまでご連絡ください。
        </p>
        <p class="mb-4">
            <strong>Xアカウント:</strong>
            <a
                class="text-gray-500 hover:text-gray-900"
                href="https://x.com/shoprogramming"
                >@shoprogramming</a
            >
        </p>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            7. プライバシーポリシーの変更
        </h2>
        <p class="mb-4">
            本プライバシーポリシーは、必要に応じて更新されることがあります。変更があった場合は、アプリ内またはウェブサイト上で通知します。変更後もアプリを利用することで、ユーザーおよび介護サービス利用者は更新後のプライバシーポリシーに同意したものとみなされます。
        </p>

        <h2 class="text-2xl font-semibold mt-6 mb-2 border-b border-gray-300">
            8. お問い合わせ
        </h2>
        <p class="mb-4">
            本プライバシーポリシーに関するお問い合わせは、以下のXアカウントまでお願いいたします。
        </p>
        <p class="mb-4">
            <strong>Xアカウント:</strong>
            <a
                class="text-gray-500 hover:text-gray-900"
                href="https://x.com/shoprogramming"
                >@shoprogramming</a
            >
        </p>
    </div>

    <Footer />
</template>
