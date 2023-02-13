import template from  './newsletter-promotion-configuration.html.twig';

const { Component, Mixin, Defaults } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('newsletter-promotion-configuration',{
    template,

    inject: [
        'repositoryFactory',
        'configService',
        'acl',
    ],
    mixins: [
        Mixin.getByName('notification'),
    ],

    data(){
        return {
            isLoading: false,
            isSaveSuccessful: false,
            config: null,
            salesChannels: [],
            promoCodeOptions:[],
        }
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },
        promotionRepository() {
            return this.repositoryFactory.create('promotion');
        },
    },

    created() {
        this.createdComponent();
        this.getPromotionProperties();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equalsAny('typeId', [
                    Defaults.storefrontSalesChannelTypeId,
                    Defaults.apiSalesChannelTypeId
                ])
            );

            this.salesChannelRepository.search(criteria, Shopware.Context.api).then(res => {
                res.add({id: null,
                    translated: {
                        name: this.$tc('sw-sales-channel-switch.labelDefaultOption')
                    }
                });
                this.salesChannels = res;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSave(){
            this.isLoading = true;
            this.$refs.configComponent.save().then(() => {
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    title: this.$tc('newsletter-promotion.action.titleSaveSuccess'),
                    message: this.$tc('newsletter-promotion.action.messageSaveSuccess')
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }
            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },

        getPromotionProperties() {
            const criteria = new Criteria();
            criteria.addAssociation('personaRules');
            criteria.addFilter(Criteria.equals('validFrom',null));
            criteria.addFilter(Criteria.equals('validUntil',null));
            criteria.addFilter(Criteria.equals('maxRedemptionsPerCustomer',1));
            criteria.addFilter(Criteria.equals('exclusive',false));
            criteria.addFilter(Criteria.equals('preventCombination',true));
            criteria.addFilter(Criteria.not('and', [
                Criteria.equals('code', null),
            ]));
            criteria.addFilter(Criteria.not('and', [
                Criteria.equals('salesChannels.salesChannelId',null),
            ]));
            criteria.addFilter(Criteria.equals('personaRules.id',null));
            criteria.addFilter(Criteria.equals('orderRules.id',null));
            criteria.addFilter(Criteria.equals('useSetGroups',false));
            criteria.addFilter(Criteria.equals('cartRules.id',null));
            criteria.addFilter(Criteria.equals('discounts.scope','cart'));
            criteria.addFilter(Criteria.equals('discounts.considerAdvancedRules',false));
            criteria.addFilter(Criteria.not('and',[
                Criteria.equals('discounts.type','fixed_unit')
            ]));
            this.promotionRepository.search(criteria, Shopware.Context.api)
                .then((entity) => {
                    entity.forEach((name) => {
                        this.promoCodeOptions.push(name);
                    });
                    return this.promoCodeOptions;
                });
        },
    }

});
