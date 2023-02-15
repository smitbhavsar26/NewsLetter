import NewsletterPlugin from './newsletter-plugin/newsletter-plugin';
const PluginManager = window.PluginManager;
PluginManager.register('NewsletterPlugin', NewsletterPlugin);
window.onload = () => {
    if (document.querySelector('#newsletter').getAttribute('data-session') == 'cart' && document.querySelector('#newsletter').getAttribute('data-newsletteruser') == 0) {
    } else {
        $('#newsletter').modal('show');
    }
}
