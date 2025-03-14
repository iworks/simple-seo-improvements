/*! Simple SEO Improvements - v2.2.7
 * http://iworks.pl/en/plugins/simple-seo-improvements/
 * Copyright (c) 2025; * Licensed GPLv2+
 */
window.simple_seo_improvements = window.simple_seo_improvements || [];
window.simple_seo_improvements.json_type_change = function(value) {
    var prefix = '#tr_json_';
    var $ = jQuery;
    $(prefix + 'org_name').hide();
    $(prefix + 'org_alt').hide();
    $(prefix + 'org_img').hide();
    $(prefix + 'org_pa_st').hide();
    $(prefix + 'person').hide();
    $(prefix + 'person_img').hide();
    $(prefix + 'other').hide();
    $(prefix + 'org_pa_l').hide();
    $(prefix + 'org_pa_r').hide();
    $(prefix + 'org_pa_pc').hide();
    $(prefix + 'org_pa_c').hide();
    switch( value ) {
        case 'person':
            $(prefix + 'person').show();
            $(prefix + 'person_img').show();
            $(prefix + 'other').show();
            break;
        case 'organization': 
            $(prefix + 'org_name').show();
            $(prefix + 'org_alt').show();
            $(prefix + 'org_img').show();
            $(prefix + 'other').show();
            $(prefix + 'org_pa_st').show();
            $(prefix + 'org_pa_l').show();
            $(prefix + 'org_pa_r').show();
            $(prefix + 'org_pa_pc').show();
            $(prefix + 'org_pa_c').show();
            break;
    }
};
jQuery(document).ready(function($) {
    var json_type = 'input[name="iworks_ssi_json_type"]';
    $(json_type).on('change', function() {
        window.simple_seo_improvements.json_type_change($(json_type + ':checked').val());
    });
    if ($(json_type)) {
        window.simple_seo_improvements.json_type_change($(json_type + ':checked').val());
    }
});
