jQuery(document).ready(function (){
    jQuery('#network-frontpage-tag').tagit({
        availableTags: window.nf_tags,
        fieldName: 'tags[]',
        beforeTagAdded: function (ev, obj){
            if (window.nf_tags.indexOf(obj.tagLabel) == -1){
                return false;
            }
        }
    });
});