import NewsletterPlugin from './newsletter-plugin/newsletter-plugin';
const PluginManager = window.PluginManager;
PluginManager.register('NewsletterPlugin', NewsletterPlugin);
window.onload = () => {
    if(document.getElementById("newsletter") != null){
        let newsLetterClick = document.querySelector('#newsletter').getAttribute('data-session');
        let loginUser = document.querySelector('#newsletter').getAttribute('data-login');
        let checkFirstTime = document.querySelector('#newsletter').getAttribute('data-newsletteruser');
        if (checkFirstTime == 0 && newsLetterClick == '') {
            $('#newsletter').modal('show');
        }
        if(loginUser == 'Guest'){
            $('#newsletter').modal('show');
        }
    }
}
