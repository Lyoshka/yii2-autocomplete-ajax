<?php

namespace lyoshka\autocompleteAjax;

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

class AutocompleteAjax2 extends InputWidget
{
    public $multiple = false;
    public $url = [];
    public $options = [];

    private $_baseUrl;
    private $_ajaxUrl;

    public function registerActiveAssets()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = ActiveAssets::register($this->getView())->baseUrl;
        }
        return $this->_baseUrl;
    }

    public function getUrl()
    {
        if ($this->_ajaxUrl === null) {
            $this->_ajaxUrl = Url::toRoute($this->url);
        }
        return $this->_ajaxUrl;
    }

    public function run()
    {
        $value = $this->model->{$this->attribute};
        $this->registerActiveAssets();

	if ( $this->attribute == 'user_id')  {
	    $str_ID = str_replace('-','_','studies-user_id');
	    $this->setID('studies-user_id');
	}

        if ($this->multiple) {
            
            $this->getView()->registerJs("
                
                $('#{$this->getId()}').keyup(function(event) {
                    if (event.keyCode == 8 && !$('#{$this->getId()}').val().length) {
                        
                        $('#{$this->getId()}-hidden').val('');
                        $('#{$this->getId()}-hidden').change();
                        
                    } else if ($('.ui-autocomplete').css('display') == 'none' && 
                        $('#{$this->getId()}-hidden').val().split(', ').length > $(this).val().split(', ').length) {
                            
                        var val = $('#{$this->getId()}').val().split(', ');
                        var ids = [];
                        for (var i = 0; i<val.length; i++) {
                            val[i] = val[i].replace(',', '').trim();
                            ids[i] = cache_{$str_ID}_1[val[i]];
                        }
                        $('#{$this->getId()}-hidden').val(ids.join(', '));
                        $('#{$this->getId()}-hidden').change();
                    }
                });
                
                $('#{$this->getId()}').keydown(function(event) {
                    
                    if (event.keyCode == 13 && $('.ui-autocomplete').css('display') == 'none') {
                        submit_{$this->getId()} = $('#{$this->getId()}').closest('.grid-view');
                        $('#{$this->getId()}').closest('.grid-view').yiiGridView('applyFilter');
                    }
                    
                    if (event.keyCode == 13) {
                        $('.ui-autocomplete').hide();
                    }
                    
                });
                
                $('body').on('beforeFilter', '#' + $('#{$this->getId()}').closest('.grid-view').attr('id') , function(event) {
                    return submit_{$this->getId()};
                });

                var submit_{$str_ID} = false;
                var cache_{$str_ID} = {};
                var cache_{$str_ID}_1 = {};
                var cache_{$str_ID}_2 = {};
                jQuery('#{$this->getId()}').autocomplete(
                {
                    minLength: 1,
                    source: function( request, response )
                    {
                        var term = request.term;

                        if (term in cache_{$str_ID}) {
                            response( cache_{$str_ID}[term]);
                            return;
                        }
                        $.getJSON('{$this->getUrl()}', request, function( data, status, xhr ) {
                            cache_{$str_ID} [term] = data;
                                
                            for (var i = 0; i<data.length; i++) {
                                if (!(data[i].id in cache_{$this->getId()}_2)) {
                                    cache_{$str_ID}_1[data[i].label] = data[i].id;
                                    cache_{$str_ID}_2[data[i].id] = data[i].label;
                                }
                            }

                            response(data);
                        });
                    },
                    select: function(event, ui)
                    {
                        var val = $('#{$this->getId()}-hidden').val().split(', ');

                        if (val[0] == '') {
                            val[0] = ui.item.id;
                        } else {
                            val[val.length] = ui.item.id;
                        }

                        $('#{$this->getId()}-hidden').val(val.join(', '));
                        $('#{$this->getId()}-hidden').change();

                        var names = [];
                        for (var i = 0; i<val.length; i++) {
                            names[i] = cache_{$str_ID}_2[val[i]];
                        }

                        setTimeout(function() {
                            $('#{$this->getId()}').val(names.join(', '));
                        }, 0);
                    }
                });
            ");
        } else {
            $this->getView()->registerJs("
                var cache_{$str_ID} = {};
                var cache_{$str_ID}_1 = {};
                var cache_{$str_ID}_2 = {};
                jQuery('#{$this->getId()}').autocomplete(
                {
                    minLength: 1,
                    source: function( request, response )
                    {
                        var term = request.term;
                        if ( term in cache_{$str_ID} ) {
                            response( cache_{$str_ID} [term] );
                            return;
                        }
                        $.getJSON('{$this->getUrl()}', request, function( data, status, xhr ) {
                            cache_{$str_ID} [term] = data;
                            response(data);
                        });
                    },
                    select: function(event, ui)
                    {
                        $('#{$this->getId()}-hidden').val(ui.item.id);
                         $('#{$this->getId()}-hidden').change();
                    }
                });
            ");
        }
        
        if ($value) {
            $this->getView()->registerJs("
                $(function(){
                    $.ajax({
                        type: 'GET',
                        dataType: 'json',
                        url: '{$this->getUrl()}',
                        data: {term: '$value'},
                        success: function(data) {

                            if (data.length == 0) {
                                $('#{$this->getId()}').attr('placeholder', 'No data found');
                            } else {
                                var arr = [];
                                for (var i = 0; i<data.length; i++) {
                                    arr[i] = data[i].label;
                                    if (!(data[i].id in cache_{$str_ID}_2)) {
                                        cache_{$str_ID}_1[data[i].label] = data[i].id;
                                        cache_{$str_ID}_2[data[i].id] = data[i].label;
                                    }
                                }
                                $('#{$this->getId()}').val(arr.join(', '));
                            }
                            $('.autocomplete-image-load').hide();
                        }
                    });
                });
            ");
        }
        
        return Html::tag('div', 
                
        Html::activeHiddenInput($this->model, $this->attribute, ['id' => $this->getId() . '-hidden', 'class' => 'form-control'])
        . ($value ? Html::tag('div', "<img src='{$this->registerActiveAssets()}/images/load.gif'/>", ['class' => 'autocomplete-image-load']) : '')
	    //. Html::activeTextInput($this->model, $this->attribute, array_merge($this->options, ['id' => $this->getId()]))	
	    //. Html::activeTextInput($this->model, $this->attribute, array_merge($this->options, ['id' => $this->model->formName() . '[' . $this->attribute . ']']))	
           . Html::textInput('', '', array_merge($this->options, ['id' => $this->getId()]))
              
            , [
                'style' => 'position: relative;'
            ]
        );
    }
}
