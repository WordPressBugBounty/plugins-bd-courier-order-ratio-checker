document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        el: '#vue-settings',
        data: {
            apiToken: bdCourierSettings.apiToken,
            usePaid: bdCourierSettings.usePaid,
            saving: false,
            successMessage: ''
        },
        methods: {
            onFocus(event) {
                event.target.style.borderColor = '#2980b9';
            },
            onBlur(event) {
                event.target.style.borderColor = '#bdc3c7';
            },
            hoverButton(event) {
                event.target.style.background = '#1c5980';
            },
            unhoverButton(event) {
                event.target.style.background = '#2980b9';
            },
            saveSettings() {
                this.saving = true;
                this.successMessage = '';
                var data = {
                    action: 'save_courier_settings',
                    apiToken: this.apiToken,
                    usePaid: this.usePaid ? 1 : 0,
                    _wpnonce: bdCourierSettings.nonce
                };
                jQuery.post(bdCourierSettings.ajaxurl, data, (response) => {
                    this.saving = false;
                    this.successMessage = response.success ? 'Settings saved successfully.' : 'Error saving settings.';
                    setTimeout(() => { this.successMessage = ''; }, 3000);
                });
            }
        }
    });
});
