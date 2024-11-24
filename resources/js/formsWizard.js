$(function(){
    
    var $sections=$('.form-section');

    function navigateTo(index){

        $sections.removeClass('current').eq(index).addClass('current');

        $('.form-navigation .previous').toggle(index>0);
        var atTheEnd = index >= $sections.length - 1;
        $('.form-navigation .next').toggle(!atTheEnd);
        $('.form-navigation [Type=submit]').toggle(atTheEnd);
        const step = document.querySelector('.step'+index);
        step.classList.add('step_selected');

    }

    //Filtra os valores que devem ser exibidos no form-section
    function curIndex(){
        return $sections.index($sections.filter('.current'));
    }

    $('.form-navigation .previous').click(function(){
        navigateTo(curIndex() - 1);
    });

    $('.form-navigation .next').click(function(){
        $('.employee-form').parsley().whenValidate({
            group:'block-'+curIndex()
        }).done(function(){
            navigateTo(curIndex()+1);
        });

    });

    $sections.each(function(index,section){
        $(section).find(':input').attr('data-parsley-group','block-'+index);
    });
    navigateTo(0);
});