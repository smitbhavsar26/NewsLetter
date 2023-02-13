import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class AjaxPlugin extends Plugin {
    init() {
        this._client = new HttpClient();
        this.save =  document.querySelector('#ajax-button');
        this.requestUrl = document.querySelector('.newsletter-subscription').value;

        this.button = this.el.children['ajax-button'];
        this.textdiv = this.el.children['ajax-display'];

        this._registerEvents();
    }

    _registerEvents() {

        this.save.addEventListener('click', (event) => {
            if(document.getElementById("form-privacy-opt-in-").checked === true)
            {
                setTimeout(function () {
                    window.location.reload();
                }, 5000);
                this._client.post(this.requestUrl, "", this._setContent.bind(this));
            }
        });
    }

    _fetch() {
        // make the network request and call the `_setContent` function as a callback
        /*setTimeout(function () {
            window.location.reload();
        }, 2000);*/
        //this._client.get('/checkout/cart', this._setContent.bind(this), 'application/json', true)
    }

    _setContent(data) {

    }
}
