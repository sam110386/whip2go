/**
 * ExcellentExport.
 * A client side Javascript export to Excel.
 *
 * @author: Jordi Burgos (jordiburgos@gmail.com)
 *
 * Based on:
 * https://gist.github.com/insin/1031969
 * http://jsfiddle.net/insin/cmewv/
 *
 * CSV: http://en.wikipedia.org/wiki/Comma-separated_values
 */

/*
 * Base64 encoder/decoder from: http://jsperf.com/base64-optimized
 */

/*jslint browser: true, bitwise: true, plusplus: true, vars: true, white: true */

var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
var fromCharCode = String.fromCharCode;
var INVALID_CHARACTER_ERR = (function() {
    "use strict";
    // fabricate a suitable error object
    try {
        document.createElement('$');
    } catch (error) {
        return error;
    }
}());

// encoder
if (!window.btoa) {
    window.btoa = function(string) {
        "use strict";
        var a, b, b1, b2, b3, b4, c, i = 0, len = string.length, max = Math.max, result = '';

        while (i < len) {
            a = string.charCodeAt(i++) || 0;
            b = string.charCodeAt(i++) || 0;
            c = string.charCodeAt(i++) || 0;

            if (max(a, b, c) > 0xFF) {
                throw INVALID_CHARACTER_ERR;
            }

            b1 = (a >> 2) & 0x3F;
            b2 = ((a & 0x3) << 4) | ((b >> 4) & 0xF);
            b3 = ((b & 0xF) << 2) | ((c >> 6) & 0x3);
            b4 = c & 0x3F;

            if (!b) {
                b3 = b4 = 64;
            } else if (!c) {
                b4 = 64;
            }
            result += characters.charAt(b1) + characters.charAt(b2) + characters.charAt(b3) + characters.charAt(b4);
        }
        return result;
    };
}

// decoder
if (!window.atob) {
    window.atob = function(string) {
        "use strict";
        string = string.replace(new RegExp("=+$"), '');
        var a, b, b1, b2, b3, b4, c, i = 0, len = string.length, chars = [];

        if (len % 4 === 1) {
            throw INVALID_CHARACTER_ERR;
        }

        while (i < len) {
            b1 = characters.indexOf(string.charAt(i++));
            b2 = characters.indexOf(string.charAt(i++));
            b3 = characters.indexOf(string.charAt(i++));
            b4 = characters.indexOf(string.charAt(i++));

            a = ((b1 & 0x3F) << 2) | ((b2 >> 4) & 0x3);
            b = ((b2 & 0xF) << 4) | ((b3 >> 2) & 0xF);
            c = ((b3 & 0x3) << 6) | (b4 & 0x3F);

            chars.push(fromCharCode(a));
            b && chars.push(fromCharCode(b));
            c && chars.push(fromCharCode(c));
        }
        return chars.join('');
    };
}


ExcellentExport = (function() {
    "use strict";
    var version = "1.3";
    var csvSeparator = ',';
    var uri = {excel: 'data:application/vnd.ms-excel;base64,', csv: 'data:application/csv;base64,'};
    var template = {excel: '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>'};
    var csvDelimiter = ",";
    var csvNewLine = "\r\n";
    var base64 = function(s) {
        return window.btoa(window.unescape(encodeURIComponent(s)));
    };
    var format = function(s, c) {
        return s.replace(new RegExp("{(\\w+)}", "g"), function(m, p) {
            return c[p];
        });
    };

    var get = function(element) {
        if (!element.nodeType) {
            return document.getElementById(element);
        }
        return element;
    };

    var fixCSVField = function(value) {
        var fixedValue = value;
        var addQuotes = (value.indexOf(csvDelimiter) !== -1) || (value.indexOf('\r') !== -1) || (value.indexOf('\n') !== -1);
        var replaceDoubleQuotes = (value.indexOf('"') !== -1);

        if (replaceDoubleQuotes) {
            fixedValue = fixedValue.replace(/"/g, '""');
        }
        if (addQuotes || replaceDoubleQuotes) {
            fixedValue = '"' + fixedValue + '"';
        }
        return fixedValue;
    };

    var tableToCSV = function(table) {
        var data = "";
        var i, j, row, col;
        for (i = 0; i < table.rows.length; i++) {
            row = table.rows[i];
            if (i == 0) {
                for (j = 0; j < row.cells.length; j++) {
                    col = row.cells[j];
                    data = data + (j ? csvDelimiter : '') + fixCSVField(col.textContent.trim());
                }
            } else {
                for (j = 0; j < row.cells.length; j++) {
                    if (j == 0) {
                        col = row.cells[j];
                        data = data + (j ? csvDelimiter : '') + fixCSVField(col.textContent.trim());
                    } else {
                        col = row.cells[j];
                        console.log(col);
                        data = data + (j ? csvDelimiter : '') + fixCSVField((col.firstChild?col.firstChild.wholeText:'').trim());
                    }
                }
            }
            data = data + csvNewLine;
        }
        return data;
    };

    var ee = {
        /** @expose */
        excel: function(anchor, table, name) {
            table = get(table);
            var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML};
            var hrefvalue = uri.excel + base64(format(template.excel, ctx));
            anchor.href = hrefvalue;
            // Return true to allow the link to work
            return true;
        },
        /** @expose */
        csv: function(anchor, table, delimiter, newLine) {
            if (delimiter !== undefined && delimiter) {
                csvDelimiter = delimiter;
            }
            if (newLine !== undefined && newLine) {
                csvNewLine = newLine;
            }
            table = get(table);
            var csvData = tableToCSV(table);
            var hrefvalue = uri.csv + base64(csvData);
            anchor.href = hrefvalue;
            console.log(anchor);
            return true;
        }
    };

    return ee;
}());


/****CSV import feature start here*******/
// Copyright 2013 Data Design Group, Inc  All Rights Reserved
function ieReadLocalFile(that, callback) {
    //alert(that.value);
    if (!that.value)
        return;
    if (that.value.length <= 0)
        return;
    var request;
    if (window.XMLHttpRequest && false) { // code for IE7+, Firefox, Chrome, Opera, Safari
        request = new XMLHttpRequest();
    }
    else {// code for IE6, IE5
        request = new ActiveXObject("Msxml2.XMLHTTP"); // Microsoft.XMLHTTP
    }
    var fn = that.value;
    //fn="file:///"+that.value.replace("\\","/");
    request.open('get', fn, true);
    request.onreadystatechange = function()
    {
        //alert(request.readystate+":"+request.status);
        if (request.readyState == 4 && (request.status == 200 || request.status == 0)) {
            callback(request.responseText);
        }
    }
    request.send();
}

function readLocalFile(that, callback)
{
    var reader = new FileReader();

    if (that.files && that.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            //document.getElementById(txtTargetName).value=e.target.result;
            callback(e.target.result);
        };//end onload()
        reader.readAsText(that.files[0]);
    }//
} // readLocaFile

function loadTextFile(f, callback)
{
    /*****file extension check ***/
    var ext, filename = $(f).val();
    ext = filename.split('.').pop();
    if (ext == 'csv') {
        //$("#overlay").show();
        (navigator.appName.search('Microsoft') > -1) ? ieReadLocalFile(f, callback) : readLocalFile(f, callback);
        //(FileReader===undefined) ? ieReadLocalFile(f, callback) : readLocalFile(f, callback) ;
    } else {
        alert('Sorry, Only CSV file allowed with right format is allowed.');
        return false;
    }
}



function fillTable(text) {

    // document.getElementById('tabresponsive').innerHTML = text;
    // var results;
    // results = Papa.parse(text);
    // console.log("Parse results:", results);
    Papa.parse(text, {
        config: {
            // base config to use for each file
        },
        before: function(file, inputElem)
        {
            $("#overlay").show();
            // executed before parsing each file begins;
            // what you return here controls the flow
        },
        error: function(err, file, inputElem, reason)
        {
            alert(reason);
            $("#overlay").hide();
            // executed if an error occurs while loading the file,
            // or if before callback aborted for some reason
        },
        complete: function(results)
        {

            //alert(results.data.length);
            var arrayData = results.data;
            if (arrayData.length > 1) {
                var header = arrayData[0];
                for (var i = 1; i < arrayData.length; i++) {

                    var gridData = arrayData[i];
                    var leftcell = gridData[0];
                    for (var j = 1; j < gridData.length; j++) {
                        var cell = leftcell + '-' + header[j];
                        $("input[title='" + cell + "']").val(gridData[j]);
                        //console.log("Parsing complete:", 'row:' + header[j] + "," + 'col:' + leftcell + ",value:" + gridData[j]);
                    }
                }
            } else {
                alert('Sorry, the CSV file seems in wrong encoding/format.');
            }
            //console.log("Parsing complete:", results);

            // executed after all files are complete
            //$("#overlay").hide();
        }
    });


}


