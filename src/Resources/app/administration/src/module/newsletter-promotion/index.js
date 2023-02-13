import "./page/newsletter-promotion-configuration";
import './components/newsletter-configuration-discount-icon';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import nlNL from './snippet/nl-NL.json';

const {Module} = Shopware;

Module.register(
    'newsletter-promotion',
    {
        type: 'plugin',
        name: 'newsletter-promotion.general.mainMenuItemGeneral',
        title: 'newsletter-promotion.general.mainMenuItemGeneral',
        description: 'newsletter-promotion.general.descriptionTextModule',
        color: '#9AA8B5',
        icon: 'default-communication-inbox',
        favicon: 'icon-module-settings.png',

        snippets: {
            'de-DE': deDE,
            'en-GB': enGB,
            'nl-NL': nlNL
        },
        routes: {
            index: {
                component: 'newsletter-promotion-configuration',
                path: 'index',
                meta: {
                    parentPath: 'sw.settings.index'
                }
            }
        },
        settingsItem: {
            name: 'newsletter-promotion',
            to: 'newsletter.promotion.index',
            label: 'newsletter-promotion.general.mainMenuItemGeneral',
            group: 'plugins',
            icon: 'default-communication-inbox'
        }
    }
);
