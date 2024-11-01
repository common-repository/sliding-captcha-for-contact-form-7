jQuery(document).ready(function () {
    lock_form_text = 'Locked : form can\'t be submited';
    unlock_form_text = 'Unlocked : form can be submited';
    jQuery('.wpcf7-sliding-captcha').QapTcha({
        txtLock: lock_form_text,
        txtUnlock: unlock_form_text,
        disabledSubmit: true,
        autoRevert: true,
        autoSubmit: false,
        PHPfile: script_url.plugin_ajax_url
    });
});