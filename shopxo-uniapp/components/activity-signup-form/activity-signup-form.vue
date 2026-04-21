<template>
    <view class="signup-form">
        <view class="form-card muying-card padding-main">
            <view class="form-header flex-row align-c margin-bottom-main">
                <text class="fw-b text-size cr-base">报名信息</text>
                <view class="muying-divider flex-1 margin-left-main"></view>
            </view>
            <view class="form-items">
                <view class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">姓名</text></view>
                    <view class="form-input margin-top-xs"><input :value="form.name" @input="on_input('name', $event)" type="text" placeholder="请输入姓名" placeholder-class="cr-grey-9" class="text-size-sm" maxlength="20" /></view>
                </view>
                <view class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">手机号</text></view>
                    <view class="form-input margin-top-xs"><input :value="form.phone" @input="on_input('phone', $event)" type="number" placeholder="请输入手机号" placeholder-class="cr-grey-9" class="text-size-sm" maxlength="11" /></view>
                </view>
                <view class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">当前阶段</text></view>
                    <view class="form-input margin-top-xs">
                        <picker :range="stageOptions" :value="stageIndex" @change="stage_change_event">
                            <view class="flex-row jc-sb align-c">
                                <text :class="'text-size-sm ' + (stageIndex >= 0 ? 'cr-base' : 'cr-grey-9')">{{ stageIndex >= 0 ? stageOptions[stageIndex] : '请选择当前阶段' }}</text>
                                <uni-icons type="right" size="16" color="#999"></uni-icons>
                            </view>
                        </picker>
                    </view>
                </view>
                <view v-if="selectedStage === 'pregnancy'" class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">预产期</text></view>
                    <view class="form-input margin-top-xs">
                        <picker mode="date" :value="form.due_date" :start="dueDateStart" @change="due_date_change_event">
                            <view class="flex-row jc-sb align-c">
                                <text :class="'text-size-sm ' + (form.due_date ? 'cr-base' : 'cr-grey-9')">{{ form.due_date || '请选择预产期' }}</text>
                                <uni-icons type="right" size="16" color="#999"></uni-icons>
                            </view>
                        </picker>
                    </view>
                </view>
                <view v-if="selectedStage === 'postpartum'" class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">宝宝生日</text></view>
                    <view class="form-input margin-top-xs">
                        <picker mode="date" :value="form.baby_birthday" :end="babyBirthdayEnd" @change="baby_birthday_change_event">
                            <view class="flex-row jc-sb align-c">
                                <text :class="'text-size-sm ' + (form.baby_birthday ? 'cr-base' : 'cr-grey-9')">{{ form.baby_birthday || '请选择宝宝生日' }}</text>
                                <uni-icons type="right" size="16" color="#999"></uni-icons>
                            </view>
                        </picker>
                    </view>
                </view>
                <view v-if="selectedStage === 'postpartum'" class="form-item br-b padding-bottom-main margin-bottom-main">
                    <view class="form-label flex-row align-c"><text class="cr-main">*</text><text class="cr-base text-size-sm margin-left-xs">宝宝月龄</text></view>
                    <view class="form-input margin-top-xs">
                        <picker :range="babyMonthAgeOptions" :value="babyMonthAgeIndex" @change="baby_month_age_change_event">
                            <view class="flex-row jc-sb align-c">
                                <text :class="'text-size-sm ' + (babyMonthAgeIndex >= 0 ? 'cr-base' : 'cr-grey-9')">{{ babyMonthAgeIndex >= 0 ? babyMonthAgeOptions[babyMonthAgeIndex] : '请选择宝宝月龄' }}</text>
                                <uni-icons type="right" size="16" color="#999"></uni-icons>
                            </view>
                        </picker>
                    </view>
                </view>
                <view class="form-item">
                    <view class="form-label flex-row align-c"><text class="cr-base text-size-sm">备注</text><text class="cr-grey-9 text-size-xs margin-left-xs">(选填)</text></view>
                    <view class="form-input margin-top-xs"><textarea :value="form.remark" @input="on_input('remark', $event)" placeholder="请输入备注信息" placeholder-class="cr-grey-9" class="text-size-sm" maxlength="200" :auto-height="false" style="height: 160rpx" /></view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    import { MuyingStage } from '@/common/js/config/muying-enum';
    import { TipMessage } from '@/common/js/config/muying-constants.js';

    var app = getApp();

    export default {
        props: {
            initialForm: { type: Object, default: function() { return {}; } },
            initialStageIndex: { type: Number, default: -1 },
            initialSelectedStage: { type: String, default: '' },
            initialBabyMonthAgeIndex: { type: Number, default: -1 },
        },

        data() {
            var stage_list = MuyingStage.getList().filter(function(v) { return v.value !== 'all'; });
            return {
                stageOptions: stage_list.map(function(v) { return v.name; }),
                stageValues: stage_list.map(function(v) { return v.value; }),
                stageIndex: this.initialStageIndex,
                selectedStage: this.initialSelectedStage,
                babyMonthAgeOptions: [],
                babyMonthAgeIndex: this.initialBabyMonthAgeIndex,
                dueDateStart: '',
                babyBirthdayEnd: '',
                form: Object.assign({
                    name: '',
                    phone: '',
                    due_date: '',
                    baby_birthday: '',
                    baby_month_age: '',
                    remark: '',
                }, this.initialForm),
            };
        },

        created() {
            this.init_baby_month_age_options();
            this.init_date_bounds();
        },

        methods: {
            on_input(field, e) {
                this.form[field] = e.detail.value;
                this.$emit('form-change', this.get_form_data());
            },

            stage_change_event(e) {
                var idx = e.detail.value;
                var stage = this.stageValues[idx] || '';
                this.stageIndex = idx;
                this.selectedStage = stage;
                this.form.due_date = '';
                this.form.baby_birthday = '';
                this.form.baby_month_age = '';
                this.babyMonthAgeIndex = -1;
                this.$emit('stage-change', { stage: stage, stageIndex: idx });
            },

            due_date_change_event(e) {
                this.form.due_date = e.detail.value;
                this.$emit('form-change', this.get_form_data());
            },

            baby_birthday_change_event(e) {
                this.form.baby_birthday = e.detail.value;
                this.$emit('form-change', this.get_form_data());
            },

            baby_month_age_change_event(e) {
                var idx = e.detail.value;
                this.babyMonthAgeIndex = idx;
                this.form.baby_month_age = idx + 1;
                this.$emit('form-change', this.get_form_data());
            },

            init_baby_month_age_options() {
                var opts = [];
                for (var i = 1; i <= 36; i++) { opts.push(i + '个月'); }
                this.babyMonthAgeOptions = opts;
            },

            init_date_bounds() {
                var now = new Date();
                var y = now.getFullYear();
                var m = String(now.getMonth() + 1).padStart(2, '0');
                var d = String(now.getDate()).padStart(2, '0');
                this.dueDateStart = y + '-' + m + '-' + d;
                this.babyBirthdayEnd = y + '-' + m + '-' + d;
            },

            validate() {
                if (!this.form.name.trim()) {
                    app.globalData.showToast(TipMessage.FORM_NAME_REQUIRED);
                    return false;
                }
                if (!this.form.phone.trim()) {
                    app.globalData.showToast(TipMessage.FORM_PHONE_REQUIRED);
                    return false;
                }
                if (!/^1[3-9]\d{9}$/.test(this.form.phone.trim())) {
                    app.globalData.showToast('请输入正确的手机号');
                    return false;
                }
                if (this.stageIndex < 0) {
                    app.globalData.showToast('请选择当前阶段');
                    return false;
                }
                if (this.selectedStage === 'pregnancy' && !this.form.due_date) {
                    app.globalData.showToast('请选择预产期');
                    return false;
                }
                if (this.selectedStage === 'postpartum' && !this.form.baby_birthday) {
                    app.globalData.showToast('请选择宝宝生日');
                    return false;
                }
                if (this.selectedStage === 'postpartum' && this.babyMonthAgeIndex < 0) {
                    app.globalData.showToast('请选择宝宝月龄');
                    return false;
                }
                return true;
            },

            get_form_data() {
                return {
                    name: this.form.name.trim(),
                    phone: this.form.phone.trim(),
                    stage: this.selectedStage,
                    due_date: this.form.due_date,
                    baby_birthday: this.form.baby_birthday,
                    baby_month_age: this.form.baby_month_age,
                    remark: this.form.remark,
                };
            },

            set_profile(profile) {
                if (!profile) return;
                if (!this.form.name && profile.user_name_view) {
                    this.form.name = profile.user_name_view;
                }
                if (!this.form.phone && profile.mobile) {
                    this.form.phone = profile.mobile;
                }
                if (profile.current_stage) {
                    var idx = this.stageValues.indexOf(profile.current_stage);
                    if (idx >= 0) {
                        this.stageIndex = idx;
                        this.selectedStage = profile.current_stage;
                    }
                }
                if (profile.due_date) {
                    this.form.due_date = profile.due_date;
                }
                if (profile.baby_birthday) {
                    this.form.baby_birthday = profile.baby_birthday;
                    var b = new Date(profile.baby_birthday);
                    var now = new Date();
                    if (!isNaN(b.getTime())) {
                        var months = (now.getFullYear() - b.getFullYear()) * 12 + (now.getMonth() - b.getMonth());
                        if (months >= 1 && months <= 36) {
                            this.babyMonthAgeIndex = months - 1;
                            this.form.baby_month_age = months;
                        }
                    }
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .form-input input,
    .form-input textarea {
        background-color: #f8f8f8;
        border-radius: 12rpx;
        padding: 16rpx 20rpx;
        width: 100%;
        box-sizing: border-box;
    }
</style>
