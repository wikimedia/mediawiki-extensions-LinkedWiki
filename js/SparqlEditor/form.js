function eventChangeSelectConfig() {
    var selectConfig = document.getElementById('config');
    var divFieldEndpoint = document.getElementById('fieldEndpoint');
    var inputFieldEndpoint = document.getElementById('endpointOther');
    var value = selectConfig.options[selectConfig.selectedIndex].value;
    if(value != "other"){
        inputFieldEndpoint.value = "";
        divFieldEndpoint.style.display = "none";
    }else{
        divFieldEndpoint.style.display = "";
    }
    console.log("change");
}

$(document).ready(function () {
    var jqselectpicker = $('.selectpicker');
    jqselectpicker.selectpicker('refresh');

    $('.selectchart').selectchart({
        action: "render",
        subtext: "classFullName",
        selected: "bordercloud.visualization.DataTable"
    });

    jqselectpicker.selectpicker('refresh');


    $("#config").on('change',eventChangeSelectConfig);

    $("#execQuery").click(function () {

        var inputValue =$('input[name=radio]:checked', '#formSparqlQuery').val();
        if(inputValue === "php"){
            $('#formSparqlQuery').submit();
        }else{
            var endpoint = "";
            var config = $('#config').val();
            var query = $('#query').val();
            var chart = $('.selectpicker').selectpicker('val');
            var options = $('#options').val();
            var logsLevel = $('#logsLevel').val();
            var optionConfigSelected = $("#config option:selected");
            var credential = optionConfigSelected.attr('credential');
            var method = optionConfigSelected.attr('method');
            var parameter = optionConfigSelected.attr('parameter');
            var wiki = "";
            var errorMessage = "";


//build container for Wiki

            wiki = "{{#sparql:\n" + query;

            if (config == "other" && endpoint != "") {
                wiki += "\n|endpoint=" + endpoint;
            } else if ( config != "") {
                wiki += "\n|config=" + config;
            // } else if (!EMPTY($config)) {
            //     //do nothing
            } else {
                errorMessage = "An endpoint Sparql or "
                    + "a configuration by default is not found.";
            }
            wiki += "\n|chart=" + chart;

            if(options !== ""){
                wiki +=  "\n|options=" + options ;
            }
            if(logsLevel !== "0"){
                wiki +=  "\n|log=" + logsLevel ;
            }
            wiki += "\n}}";

            $("#consoleWiki").text(
                wiki !="" ? wiki : errorMessage
            );

//build container for html

            $('#result').children().remove();

            $('#tabSparqlQuery a[href="#resultTab"]').tab('show')
            if(credential == 'true'){
                $('#result').html("<b>This SPARQL service is not accessible via Javascript.</b>")
            }else{
                if (config == "other"){
                    endpoint = $('#endpointOther').val();
                }else{
                    endpoint = optionConfigSelected.attr('endpoint');
                }
                sgvizler2.create(
                    'result',
                    endpoint,
                    query,
                    chart,
                    options,
                    logsLevel,
                    '' ,
                    method,
                    parameter
                );

                sgvizler2.containerDrawAll({
                    googleApiKey: googleApiKey ,
                    osmAccessToken: osmAccessToken ,
                    path: mw.config.get('wgScriptPath') + "/extensions/LinkedWiki/node_modules/sgvizler2/build/browser"
                });
            }
        }

        // $("#result").html("")
        // $("#console").html("")
        //
        //
        //
        // $("#consoleHtml").text(
        // )
        //
        // $("#consoleScript").text(
        // )

    });

    $("#seeDoc").click(function () {
        var url = sgvizler2.getChartDoc(
            $('.selectpicker').selectpicker('val')
        )
        window.open(url, '_blank');
    });

    $('input[type="radio"]').click(function(){
        if( $(this).prop("checked") ) {
            var inputValue = $(this).attr("value");
            if(inputValue == "php"){
                $('#sgvizlerInputsForm').hide();
            }else{
                $('#sgvizlerInputsForm').show();
            }
        }
    });


});

