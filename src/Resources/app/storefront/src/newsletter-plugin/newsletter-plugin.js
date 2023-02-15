import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class AjaxPlugin extends Plugin {
    init()
    {
        this._client = new HttpClient();
        this.save =  document.querySelector('#ajax-button');
        this.requestUrl = document.querySelector('.newsletter-subscription').value;

        this.button = this.el.children['ajax-button'];
        this.textdiv = this.el.children['ajax-display'];

        this._registerEvents();
    }

    _registerEvents()
    {
        this.save.addEventListener('click', (event) => {
            if (document.getElementById("form-privacy-opt-in-").checked === true) {
                setTimeout(function () {
                    window.location.reload();
                }, 5000);
                this._client.post(this.requestUrl, "", this._setContent.bind(this));
            }
        });
    }
    _setContent(data)
    {

    }
}
