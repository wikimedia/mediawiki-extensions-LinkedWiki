jQuery.fn.table2CSV = function(pOptions) {
    var options = jQuery.extend({
        separator: ',',
        header: [],
        delivery: 'popup' // popup, value
    },
    pOptions);

    var csvData = [];
    var headerArr = [];
    var el = this;

    //header
    var numCols = options.header.length;
    var tmpRow = []; // construct header avalible array

    if (numCols > 0) {
        for (var i = 0; i < numCols; i++) {
            tmpRow[tmpRow.length] = formatData(options.header[i]);
        }
    } else {
        $(el).filter(':visible').find('th').each(function() {
            if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        });
    }

    row2CSV(tmpRow);

    // actual data
    $(el).find('tr').each(function() {
        var tmpRow = [];
        $(this).filter(':visible').find('td').each(function() {
            if ($(this).css('display') != 'none') tmpRow[tmpRow.length] = formatData($(this).html());
        });
        row2CSV(tmpRow);
    });
    if (options.delivery == 'popup') {
        var mydata1 = csvData.join('\n');
        return popup(mydata1);
    } else {
        var mydata2 = csvData.join('\n');
        return mydata2;
    }

    function row2CSV(tmpRow) {
        var tmp = tmpRow.join(''); // to remove any blank rows
        // alert(tmp);
        if (tmpRow.length > 0 && tmp !== '') {
            var mystr = tmpRow.join(options.separator);
            csvData[csvData.length] = mystr;
        }
    }
    function formatData(input) {
        // replace " with “
        var regexp1 = new RegExp(/["]/g);
        var output1 = input.replace(regexp1, "“");
        //HTML
        var regexp2 = new RegExp(/\&lt;[^\&lt;]+\&gt;/g);
        var output2 = output1.replace(regexp2, "");
        if (output2 === "") return '';
        return '"' + output2 + '"';
    }
    function popup(data) {
        var generator = window.open('', 'csv', 'height=400,width=600');
        generator.document.write('<html><head><title>CSV</title>');
        generator.document.write('</head><body >');
        generator.document.write('<textArea cols=70 rows=15 wrap="off" >');
        generator.document.write(data);
        generator.document.write('</textArea>');
        generator.document.write('</body></html>');
        generator.document.close();
        return true;
    }
};

$( function () {
    // This code must not be executed before the document is loaded. 
    
 //$("div").css("border","9px solid purple");
 
  $('table').each(function() {
    var $table = $(this);
    var $l = $table.find(".csv");
    $l.click(function() {
	var csv = $table.table2CSV({delivery:'value'});
	window.location.href = 'data:text/csv;charset=UTF-8,'+
				encodeURIComponent(csv);
	});
    
//$('#example1').table2CSV({

//separator : ';',

//delivery:'value',

//header:['prefix','Employee Name','Contact']

//}));
    

  });
 
});
