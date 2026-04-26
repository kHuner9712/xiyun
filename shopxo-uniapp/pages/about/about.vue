<template>
    <view :class="theme_view">
        <view class="padding-main tc">
            <view class="bg-white border-radius-main padding-main">
                <view class="padding-vertical-xxl">
                    <!-- logo -->
                    <image class="logo circle br-f5 padding-sm dis-block auto margin-top-xl" :src="logo" mode="aspectFill"></image>
                    <!-- 名称 -->
                    <view class="margin-top-sm text-size">{{title}}</view>
                    <!-- app管理 -->
                    <component-app-admin ref="app_admin"></component-app-admin>
                    <!-- 简介 -->
                    <view class="margin-top-xxxxl cr-base text-size-sm">{{describe}}</view>
                    <!-- 协议 -->
                    <view class="margin-top-xxxxl padding-vertical-xxxxl">
                        <text class="cp cr-blue margin-right" data-value="userregister" @tap="agreement_event">{{ $t('login.login.2v11we') }}</text>
                        <text class="cp cr-blue margin-left" data-value="userprivacy" @tap="agreement_event">{{ $t('login.login.myno2x') }}</text>
                    </view>
                    <!-- 数据删除申请 -->
                    <view class="margin-top-main">
                        <text class="cp cr-blue" @tap="privacy_request_event">数据删除申请说明</text>
                    </view>
                </view>
            </view>
            <view class="margin-top cr-grey-c">Copyright 2018-{{year}} by {{title}}</view>
        </view>

        <!-- 公共 -->
        <component-common ref="common" :propIsAppAdmin="false"></component-common>
    </view>
</template>
<script>
    const app = getApp();
    import componentCommon from '@/components/common/common';
    import componentAppAdmin from '@/components/app-admin/app-admin';
    export default {
        data() {
            return {
                theme_view: app.globalData.get_theme_value_view(),
                logo: app.globalData.get_application_logo_square(),
                title: app.globalData.get_application_title(),
                describe: app.globalData.get_application_describe(),
                year: (new Date()).getFullYear(),
            };
        },

        components: {
            componentCommon,
            componentAppAdmin
        },
        onLoad(params) {
            // 调用公共事件方法
            app.globalData.page_event_onload_handle(params);
        },

        onShow() {
            // 调用公共事件方法
            app.globalData.page_event_onshow_handle();

            // 公共onshow事件
            if ((this.$refs.common || null) != null) {
                this.$refs.common.on_show();
            }

            // app管理
            if ((this.$refs.app_admin || null) != null) {
                this.$refs.app_admin.init();
            }
        },

        methods: {
            // 协议事件
            agreement_event(e) {
                var value = e.currentTarget.dataset.value || null;
                if (value == null) {
                    app.globalData.showToast(this.$t('login.login.4wc3hr'));
                    return false;
                }
            
                var key = 'agreement_' + value + '_url';
                var url = app.globalData.get_config('config.' + key) || null;
                if (url == null) {
                    app.globalData.showToast(this.$t('login.login.x0nxxf'));
                    return false;
                }
            
                app.globalData.open_web_view(url);
            },

            privacy_request_event() {
                uni.navigateTo({
                    url: '/pages/agreement/agreement?type=privacy',
                });
            },
        }
    };
</script>
<style>
    @import './about.css';
</style>