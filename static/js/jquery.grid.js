(function($){
    // ??? how to deal with nested table?
    var getCellPosition = function(cell) {
        var table = $(cell).parents('table');
        var tr = $(cell).parents('tr');
        var row = table.children('tbody').children('tr').index(tr);
        var col = tr.children('td.data').index(cell);
        return {row:row,col:col};
    }
    // close editor
    function closeEditor(editor) {
        var table = $(editor).parents('table');
        table.css('z-index', '');
        editor.close();
    }
    var onAppendRow = function(event) {
        var target = event.target;
        var td = target.parentNode;
        var tr = td.parentNode;
        var table = $(target).parents('table');
        if (table[0].settings.onAppendRow) {
            table[0].settings.onAppendRow();
        } else {
            if (table[0].settings.log) {
                var $logTable = $(table[0].settings.log.target);
                $logTable[0].init();
                var r = $logTable.find('tbody tr').last(), seq = 0;
                while (r.children('td.data').first().html() == 0) {
                    r = r.prev();
                };
                r.length == 1 && (seq = parseInt(r.children('td.data').first().html()));
                $logTable[0].appendRow([seq+1, 0]);
            }
            table[0].appendRow();
            table[0].onModified();
        }
    };
    function onInsertRow(event) {
        var target = event.target;
        var td = target.parentNode;
        var tr = td.parentNode;
        var table = $(target).parents('table');
        if (table[0].settings.onInsertRow) {
            table[0].settings.onInsertRow(tr);
        } else {
            if (table[0].settings.log) {
                var $logTable = $(table[0].settings.log.target);
                $logTable[0].init();
                var seq = table.find('tbody tr').index(tr) + 1;
                var linkRow = null;
                $logTable.find('tbody tr').each(function(){
                    $(this).children('td.data').eq(0).html() == seq && (linkRow = $(this));
                });
                if (linkRow) {
                    var currentSeq = parseInt(linkRow.children('td.data').eq(0).html());
                    var newRow = $logTable[0].insertRow(linkRow, [currentSeq, 0]);
                    $(newRow).nextAll().each(function(){
                        var seq = parseInt($(this).children('td.data').eq(0).html());
                        seq != 0 && $(this).children('td.data').eq(0).html(seq+1);
                    });
                    var oldValue = linkRow.find('select').data('oldValue2');
                    linkRow.find('select').val(oldValue);
                    var newState = $(newRow).find('select option:selected').html();
                }
            }
            table[0].insertRow(tr);
            table[0].onModified();
        }
    }
    // delete one row
    function onDeleteRow(event) {
        var target = event.target;
        var td = target.parentNode;
        var tr = td.parentNode;
        var table = $(target).parents('table');
        if (table[0].settings.onDeleteRow) {
            table[0].settings.onDeleteRow(tr);
        } else {
            if (table[0].settings.log) {
                var $logTable = $(table[0].settings.log.target);
                $logTable[0].init();
                var seq = table.find('tbody tr').index(tr) + 1;
                var linkRow = null;
                $logTable.find('tbody tr').each(function(){
                    $(this).children('td.data').eq(0).html() == seq && (linkRow = $(this));
                })
                if (linkRow) {
                    var seq = parseInt(linkRow.children('td.data').eq(0).html());
                    linkRow.children('td.data').eq(0).html(0);
                    $(linkRow).find('select').html($logTable[0].Actions.Delete);
                    linkRow.nextAll().each(function(){
                        var v = $(this).children('td.data').eq(0).html();
                        $(this).children('td.data').eq(0).html(v-1);
                    });
                }
            }
            $(tr).remove();
            table[0].onModified();
        }
    }
    function onDeleteCol(event) {
        var target = event.target;
        var $td = $(target).parent();
        var $tr = $td.parent();
        var index = $tr.children().index($td);
        var table = $(target).parents('table');
        table.find('tr').each(function(){
            $(this).children().eq(index).remove();
        });
    }
    function onInsertCol(event) {
    }
    /**
     * when click one data cell, show inner data cell editor.
     *
     * support 
     * --indicateSelected
     * --multiSelection
     * --inlineEditor
     * 
     */
    var onClickDataCell = function(event) {
        //
        var target = event.target;
        // the nested table no 'grid' class
        // so the 'table' allways the root table.
        var table = $(target).parents('table.grid');
        // if existed nested table, the target maybe a plain 'td'
        // ???
        if ($(target).parents('table').length > 1) {
            var table2 = $(target).parents('table');
            var targetRootCell = table2.parents('td.data')[0];
        } else {
            var targetRootCell = target;
        }
        /**
         * change row state of root table.
         */
        var posRootCell = getCellPosition(targetRootCell);
        if (table[0].settings.indicateSelected) {
            if (table[0].settings.multiSelection && event.ctrlKey) {
                var tr = $(targetRootCell).parent('tr');
                if (table.children('tbody').children('tr.selected').index(tr) >= 0) {
                    $(tr).removeClass('selected');
                } else {
                    $(tr).addClass('selected');
                }
            } else { 
                table.children('tbody').children('tr.selected').removeClass('selected');
                table.children('tbody').children('tr').eq(posRootCell.row).addClass('selected');
            }
        }

        /**
         * notify the grid creater that one cell is clicked.
         */
        if (table[0].settings.clickDataCell) {
            var keepon = table[0].settings.clickDataCell(posRootCell, targetRootCell, event);
            if (!keepon) return;
        }
        if (target.tagName.toLowerCase() == 'a') {
            return;
        } else if (target.tagName.toLowerCase() == 'input') {
            return;
        } else if (target.tagName.toLowerCase() == 'button') {
            return;
        }
        //
        event.preventDefault();
        event.stopPropagation();
        /**
         * if not readonly, show inner editor.
         */
        if (table[0].settings.readonly) return;
        if (!table[0].settings.ignoreFieldReadonly 
            && table[0].fields 
            && table[0].fields[posRootCell.col] 
            && table[0].fields[posRootCell.col].readonly) {
                return;
        }
        // close opened editor
        $('div.grid.input').each(function(){
            if ($.contains(this, target) == false){
                closeEditor(this);
            }
        });
        //
        var w = $(target).width();
        var h = $(target).height();
        var position = $(target).position();
        var editor = $('<div>').addClass('grid input');
        var options = {
            change:function(value) {
                $(target).data('newValue', value);
                table[0].onModified();
            },
            keydown:function(event) {
                var code = event.keyCode || event.which;
                if (code == 9) { // tab key
                    event.preventDefault();
                    var tds = table.find('td.data');
                    var upper = tds.length - 1;
                    var td = $(event.target).parents('td.data');
                    var i = tds.index(td);
                    if (i < upper) {
                        var next = tds.eq(++i);
                        next.click();
                    } else {
                        if (table[0].settings.readonly == false) {
                            var tr = table[0].appendRow();
                            $(tr).children('.data').eq(0).click();
                        }
                    }
                } else {
                    if (!table.hasClass('modified')) {
                        setTimeout(function(){
                            table[0].onModified()
                        }, 300);
                    }
                }
            },
            close:function() {
                var oldValue = $(target).data('oldValue');
                var newValue = $(target).data('newValue');
                if (newValue != oldValue ){
                    table[0].onModified();
                    $(target).html(newValue);
                } else {
                    $(target).html(oldValue);
                }
                editor.remove();
                $(target).removeData('oldValue');
                $(target).removeData('newValue');
            },
            onChooseAdvice:function(adviceId) {
                var pos = getCellPosition(target);
                table[0].chooseAdvice(pos.row, pos.seq, adviceId);
            }
        };
        editor.AdviceInput(options);
        var text = $(target).html();
        $(target).data('oldValue', text);
        $(target).append(editor);
        //
        var columnSeq = $(target).parent('tr').children('.data').index(target);
        table[0].loadDataAdvices(posRootCell, editor[0].advices);
        //
        // ie6,7 z-index bug
        table.css('z-index', '999');
        editor[0].show(w, h, position, text);
    }
    // whether click within editor, if not then close the editor.
    $(document).click(function(event){
        $('div.grid.input').each(function(){
            var target = event.target;
            if ($.contains(this, target) == false){
                closeEditor(this);
            }
        });
    });
    /**
    * extract all content of a talte to an array.
    * support nested table.
    */
    function tableToArray2(table) {
        var data = [];
        $(table).children('tbody').children('tr').each(function(){
            // current table's one row.
            var row = [];
            $(this).children('td.data').each(function(){
                var html = $(this).html();
                if ($(this).children('table').length > 0) {
                    value = tableToArray2($(html));
                } else {
                    value = html;
                }
                row.push(value);
            });
            data.push(row);
        });
        return data;
    }
    var addButton = function() {
        return $("<input type='button' class='add' value='添加'>")
        .css('float', 'left')
        .click(onAppendRow);
    }
    var delButton = function() {
        var btnDel = document.createElement('div');
        $(btnDel).addClass('delete');
        $(btnDel).click(onDeleteRow);
        return btnDel;
    }
    var insButton = function() {
        var btnIns = document.createElement('div');
        $(btnIns).addClass('insert');
        $(btnIns).click(onInsertRow);
        return btnIns;
    }
    var delColButton = function() {
        var btnDel = document.createElement('div');
        $(btnDel).addClass('delete');
        $(btnDel).click(onDeleteCol);
        return btnDel;
    }
    var insColButton = function() {
        var btnIns = document.createElement('div');
        $(btnIns).addClass('insert');
        $(btnIns).click(onInsertCol);
        return btnIns;
    }
    /**
     *
     */
    function newEmptyRow(table) {
        // new row
        var tr = document.createElement('tr');
        if (table.settings.showSequence) {
            var tdSeq = document.createElement('td');
            $(tdSeq).addClass('seq');
            var seq = $(table).children('tbody').children('tr').length + 1;
            $(tdSeq).html(seq);
            tr.appendChild(tdSeq);
            
        }
        if (table.settings.draggable) {
            var tdHandle = document.createElement('td');
            $(tdHandle).addClass('dragHandle');
            tr.appendChild(tdHandle);
        }
        // last column for row operations.
        if (table.settings.readonly == false){
            var tdOp = document.createElement('td');
            tdOp.appendChild(delButton());
            tdOp.appendChild(insButton());
            tr.appendChild(tdOp);
        }
        return {tr:tr, seq:tdSeq, dragHandle:tdHandle, op:tdOp};
    }
    /**
    * @param value
    * @param int row
    * @param string name
    * @param options how to handle the value
    * 
    */
    function valueToCell(value, row, name, options) {
        var instruct = null, fullname = String(name);
        if (options && options.name && options.name.length > 0) {
            fullname = options.name + '.' + fullname;
        }
        if (options && options.beforeCell) {
            instruct = options.beforeCell(value, fullname, row);
        }
        if (instruct) {
            if (instruct.skip) {
                return false;
            } else if (instruct.altered !== undefined 
                && instruct.altered !== value
            ) {
                value= instruct.altered; 
            }
        }
        var tdCell = document.createElement('td');
        $(tdCell).addClass('data').click(onClickDataCell);
        if (typeof value == 'object') {
            var valueOptions = $.extend(
                {} ,options
                ,{name: fullname}
            );
            var gridOptions = {
                readonly:true
                ,draggable:false
                ,tableClass: 'nested table table-bordered'
                ,showHeader: false
                ,showFooter: false
            };
            if (instruct && instruct.fields) {
                gridOptions.fields = instruct.fields;
            }
            $('<table>').Grid(gridOptions, value, valueOptions).appendTo(tdCell);
        } else {
            tdCell.innerHTML = value;
        }
        return tdCell;
    }
    /**
    * add cell between 'handle' cell and 'op' cell if need.
    *
    * @param Object row
    * @param cell to be added.
    */
    function addCell(row, cell) {
        if (row.op) {
            $(row.op).before(cell);
        } else {
            $(row.tr).append(cell);
        }
    }
    /**
    *
    */
    function refreshRowSequence(table) {
        if (!table.settings.showSequence) {
            return;
        }
        $(table).children('tbody').children('tr').each(function(index){
            $(this).children('td:first-child').html(index + 1);
        });
    }
    /**
     *
     * fill an object into a table.
     *
     * @param table
     * @param Array data
     * @param Boolean asArray
     * @param Object options
     */
    function fillObject(table, data, dataOptions) {
        if (dataOptions && dataOptions.objectAsArray) {
            var rowOfObject = newEmptyRow(table);
            $(table).append(rowOfObject.tr);
        }
        var pindex = 0;
        for (var p in data) {
            var value = data[p];
            if (dataOptions && dataOptions.objectAsArray) { // show as array
                var tdCell = valueToCell(value, 0, p, dataOptions);
                if (!tdCell) {
                    continue;
                }
                addCell(rowOfObject, tdCell);
            } else { // show as object
                var rowOfProp = newEmptyRow(table);
                $(table).append(rowOfProp.tr);
                var tdCell = document.createElement('td');
                $(tdCell).addClass('prop').html(p);
                addCell(rowOfProp, tdCell);
                var tdCell = valueToCell(value, pindex, p, dataOptions);
                addCell(rowOfProp, tdCell);
                pindex++;
            }
        }
    }
    /**
    * fill one row.
    *
    * @param r
    */
    var fillRow = function(table, row, r, data, dataOptions){
        if (typeof data == 'object') {
            if (table.fields && table.fields[0].objField != undefined) {
                for (var f in table.fields) {
                    var name = table.fields[f].objField;
                    var value = data[name];
                    if (!(tdCell = valueToCell(value, r, name, dataOptions))) {
                        continue;
                    }
                    addCell(row, tdCell);
                }
            } else {
                for (var c in data) { 
                    var value = data[c];
                    if (!(tdCell = valueToCell(value, r, c, dataOptions))) {
                        continue;
                    }
                    addCell(row, tdCell);
                }
            }
        } else {
            if (!(tdCell = valueToCell(data, r, '0', dataOptions))) {
                return;
            }
            addCell(row, tdCell);
        }
        //
        if (dataOptions && dataOptions.beforeCloseRow) {
            var appended = dataOptions.beforeCloseRow(r, data);
            if (appended) {
                tdCell = document.createElement('td');
                tdCell.innerHTML = appended;
                $(tdCell).click(onClickDataCell);
                addCell(row, tdCell);
            }
        }
    };
    /**
     * fill an array into a table.
     * todo: add elements to row
     *
     * @param table
     * @param Array data
     * @param Object options
     */
    function fillArray(table, data, dataOptions) {
        for (var r = 0, len = data.length; r < len; r++) {
            var oneRow = data[r];
            if (table.settings.readonly 
                && oneRow.join !== undefined
                && oneRow.join('').replace(/(^\s*)|(\s*$)/g,"").length == 0
            ) {// jump empty row
                continue;
            }
            var row = newEmptyRow(table);
            $(table).append(row.tr);
            fillRow(table, row, r, oneRow, dataOptions);
        }
    }
    /*
    * grid definition
    * options
    *  --title
    *  --readonly
    *  --tableWidth
    *  --ignoreFieldReadonly
    *  --columnEditable
    *  --fields
    *  ----name
    *  ----type
    *  --clickDataCell(pos(row,col), cell, event):keepon?
    *  --loadDataAdvices
    *  --chooseAdvice
    *  --onModified
    *  --tableClass
    *  --showSequence
    *  --draggable
    *  --pagination
    *  --onAppendRow
    *  --onDeleteRow
    *  ----limit
    *  --log
    *  ----target
    *
    * dataOptions
    *  --beforeCell function(value, name, row)
    *  --beforeCloseRow function(row)
    *
    *
    */
    $.fn.Grid = function(options, gridData, dataOptions) {
        var settings = $.extend({
            readonly: false
            ,draggable: true
            ,showSequence: false
            ,indicateSelected: true
            ,showHeader: true
            ,showFooter: true
            ,columnEditable: false
        }, options);
        /*
        * build grid
        */
        this.each(function(){
            //is a table?
            if (this.nodeName.toLowerCase() != 'table') return;
            //
            var _table = this; 
            _table.settings = settings;
            _table.fields = settings.fields;
            /*
            * public methods
            */
            /**
            * fill an array to table. support nested array.
            *
            * @param object/array data
            * @param object options2
            */
            _table.fill = function(data, dataOptions) {
                if (_table.settings.onRefresh) {
                    _table.settings.onRefresh();
                }
                $(_table).data('grid-data', data);
                $(_table).children('tbody').remove();
                if (data && data.join) { // data is array
                    if (dataOptions && dataOptions.objectAsArray == undefined) {
                        dataOptions.objectAsArray = true;
                    }
                    fillArray(_table, data, dataOptions);
                } else { // data is object
                    fillObject(_table, data, dataOptions);
                }
            }
            // append empty row
            _table.appendRow = function(data) {
                var row = newEmptyRow(_table);
                // data cells
                if (data) {
                    var existing = $(_table).data('grid-data');
                    fillRow(_table, row, existing.length, data, dataOptions);
                    if (existing) {
                        existing.push(data);
                    }
                } else if (_table.fields) {
                    var fieldNum = _table.fields.length;
                    for (var i = 0; i < fieldNum; i++) {
                        var tdCell = document.createElement('td');
                        $(tdCell).click(onClickDataCell);
                        $(tdCell).addClass('data');
                        addCell(row, tdCell);
                    }
                }
                // append to table
                $(_table).append(row.tr);
                //
                refreshRowSequence(_table);
                //
                return row.tr;
            }
            //
            _table.insertRow = function(row, data) {
                var row2 = newEmptyRow(_table);
                // data cells
                if (data) {
                    var rowIndex = $(row).prop('sectionRowIndex');
                    fillRow(_table, row2, rowIndex, data, dataOptions);
                    var existing = $(_table).data('grid-data');
                    if (existing) {
                        existing.splice(0, 0, data);
                    }
                } else if (_table.fields) {
                    var fieldNum = _table.fields.length;
                    for (var i = 0; i < fieldNum; i++) {
                        var tdCell = document.createElement('td');
                        $(tdCell).click(onClickDataCell);
                        $(tdCell).addClass('data');
                        addCell(row2, tdCell);
                    }
                }
                $(row).before(row2.tr);
                return row2.tr;
                //
            }
            // prepend empty row
            _table.prependRow = function(data) {
                var row = newEmptyRow(_table);
                // data cells
                if (data) {
                    fillRow(_table, row, 0, data, dataOptions);
                    var existing = $(_table).data('grid-data');
                    if (existing) {
                        existing.splice(0, 0, data);
                    }
                } else if (_table.fields) {
                    var fieldNum = _table.fields.length;
                    for (var i = 0; i < fieldNum; i++) {
                        var tdCell = document.createElement('td');
                        $(tdCell).click(onClickDataCell);
                        $(tdCell).addClass('data');
                        addCell(row, tdCell);
                    }
                }
                // append to table
                if ($(_table).children('tbody').length > 0) {
                    $(_table).children('tbody').prepend(row.tr);
                    refreshRowSequence(_table);
                } else {
                    $(_table).append(row.tr);
                }
                //
                return row.tr;
            }
            // serialize all data to an array
            _table.serialize = function() {
                var data = tableToArray2(_table);
                return data;
            }
            // blur the grid 
            _table.blur = function() {
                $('div.grid.input').each(function(){
                    closeEditor(this);
                });
            }
            // load editor's advices
            _table.loadDataAdvices = function(column, callback) {
                if (settings.loadDataAdvices) {
                    settings.loadDataAdvices(column, callback);
                }
            }
            //
            _table.chooseAdvice = function(rowSeq, colSeq, adviceId) {
                if (settings.chooseAdvice) {
                    settings.chooseAdvice(rowSeq, colSeq, adviceId)
                }
            }
            //
            _table.refresh = function() {
                $(_table).removeClass('modified');
            }
            //
            _table.onModified = function() {
                if ($(_table).hasClass('modified'))
                    return;
                $(_table).addClass('modified');
                if (settings.onModified)
                    settings.onModified();
            }
            //
            _table.selectedData = function() {
                if (this.selectedRow().length == 0) {
                    return false;
                }
                var allData = [];
                $(_table).children('tbody').children('tr.selected').each(function(){
                    var rowIndex = $(this).prop('sectionRowIndex');
                    var data = $(_table).data('grid-data')[rowIndex];
                    allData.push(data);
                });
                if (settings.multiSelection) {
                    return allData; 
                } else {
                    return allData[0]; 
                }
            }
            //
            _table.selectedRow = function() {
                return $(_table).children('tbody').children('tr.selected');
            }
            //
            _table.removeSelectedRow = function() {
                var rows = this.selectedRow()
                for (var i = 0; i < rows.length; i++) {
                    _table.removeRow(rows[i]);
                }
            }
            //
            _table.removeRow = function(row) {
                var rowIndex = $(row).prop('sectionRowIndex');
                $(row).remove();
                //
                refreshRowSequence(_table);
                //
                var data = $(_table).data('grid-data');
                data.splice(rowIndex, 1);
            }
            _table.mytitle = function(val) {
                if (arguments.length == 0) {
                    return $(_table).find('caption').html(); 
                } else {
                    $(_table).find('caption').html(val); 
                }
            }
            _table.readonly = function(value) {
                if (_table.settings.readonly == value) {
                    return;
                }
                _table.settings.readonly = value;
                if (_table.settings.readonly) {
                    $(_table).find('col:last-child').remove();
                    $(_table).find('input.add').remove();
                    $(_table).find('thead tr td:last-child').remove();
                    $(_table).find('tbody tr td:last-child').remove();
                    var colspan = $(_table).find('tfoot tr td').prop('colspan');
                    $(_table).find('tfoot tr td').prop('colspan', colspan - 1);
                } else {
                    $(_table).find('colgroup').append('<col>');
                    $(_table).find('thead tr').append('<td>');
                    $(_table).find('tbody tr').each(function(){
                        var actions = $('<td>').append(delButton()).append(insButton());
                        $(this).append(actions);
                    });
                    var colspan = $(_table).find('tfoot tr td').prop('colspan');
                    $(_table).find('tfoot tr td').prop('colspan', colspan + 1);
                    $(_table).find('tfoot tr td').append(addButton());
                }
            }
            /*
            * initialize
            */
            if (settings.tableClass) {
                $(_table).empty().addClass(settings.tableClass);
            } else {
                $(_table).empty().addClass('grid table table-bordered');
            }
            // set caption
            if (settings.title) {
                var caption = $('<caption>');
                caption.html(settings.title);
                $(_table).append(caption);
            }
            // 20px for scrollbar
            if (settings.tableWidth) {
                $(_table).width(settings.tableWidth - 20);
            } else {
                if (!$(_table).hasClass('nested')) {
                    var tableWidth = $(_table).parent().width() - 20;
                    tableWidth > 0 && $(_table).width(tableWidth);
                }
            }
            //
            var defaultColNum = 0;
            settings.showSequence && defaultColNum++;
            settings.draggable && defaultColNum++;
            !settings.readonly && defaultColNum++;

            if (_table.fields) {
                //colgroups and thead
                $(_table).append('<colgroup></colgroup>');
                settings.showHeader && $(_table).append('<thead><tr></tr></thead>');
                if (settings.showSequence) {
                    $('<col width="40px"/>').appendTo($(_table).find('colgroup'));
                    $('<td>').appendTo($(_table).find('thead tr'));
                }
                if (settings.draggable) {
                    $('<col width="40px"/>').appendTo($(_table).find('colgroup'));
                    $('<td>').appendTo($(_table).find('thead tr'));
                }
                //
                var weight = 0, fixedWidth = 0;
                for (var i in _table.fields) {
                    var field = _table.fields[i];
                    if (field.width) {
                        fixedWidth += parseInt(field.width) + 1;
                    } else {
                        weight += parseInt(field.weight || 1);
                    }
                }
                var weightWidth = (tableWidth - (41 * defaultColNum) - fixedWidth) / weight; 
                for (var f in _table.fields) {
                    var field = _table.fields[f];
                    var jqCol = $('<col>');
                    field.class && jqCol.addClass(field.class);
                    if (field.width && field.width > 0) {
                        jqCol.css({width:field.width}).appendTo($(_table).find('colgroup'));
                    } else {
                        var colWidth = (field.weight || 1) * weightWidth;
                        if (colWidth && colWidth > 0) {
                            jqCol.css({width:colWidth}).appendTo($(_table).find('colgroup'));
                        } else {
                            jqCol.appendTo($(_table).find('colgroup'));
                        }
                    }
                    $('<td>'+field.name+'</td>').appendTo($(_table).find('thead tr'));
                }
                if (!settings.readonly) {
                    //last column
                    $('<col width="40px"/>').appendTo($(_table).find('colgroup'));
                    $('<td>').appendTo($(_table).find('thead tr'));
                }
                // column edit bar
                if (settings.columnEditable) {
                    $(_table).append('<tfoot><tr></tr></tfoot>');
                    var i = 0,l = _table.fields.length + defaultColNum;
                    for (;i<l;i++) {
                        $('<td>').appendTo($(_table).find('tfoot tr'));
                    }
                }
                //footer
                if (settings.showFooter) {
                    $(_table).append('<tfoot><tr></tr></tfoot>');
                    var operations = $('<td>');
                    operations.prop('colspan', _table.fields.length + defaultColNum);
                    operations.appendTo($(_table).find('tfoot tr'));
                    // default operations
                    settings.readonly == false && operations.append(addButton());
                    // pagination
                    if (settings.pagination) {
                        var btnAppend = $("<input type='button' class='next' value='下一页'>");
                        btnAppend.css('float', 'right');
                        btnAppend.click(function() {
                            pagination = $(_table).data('pagination');
                            var currentData = pagination.next();
                            if (currentData !== false) {
                                _table.fill(currentData, dataOptions);
                                !pagination.hasNext() && $(_table).find('input.next').css('display', 'none'); 
                                $(_table).find('input.prev').css('display', 'block'); 
                            }
                        });
                        operations.append(btnAppend);
                        btnAppend = $("<input type='button' class='prev' value='上一页'>");
                        btnAppend.css('float', 'right');
                        btnAppend.click(function() {
                            pagination = $(_table).data('pagination');
                            var currentData = pagination.prev();
                            if (currentData !== false) {
                                _table.fill(currentData, dataOptions);
                                !pagination.hasPrev() && $(_table).find('input.prev').css('display', 'none'); 
                                $(_table).find('input.next').css('display', 'block'); 
                            }
                        });
                        operations.append(btnAppend);
                    }
                }
            }
            if (gridData) {
                if (settings.pagination) {
                    var pagination = new Pagination(gridData, settings.pagination.limit);
                    var currentData = pagination.current();
                    $(_table).data('pagination', pagination);
                    $(_table).find('input.prev').css('display', 'none');
                    !pagination.hasNext() && $(_table).find('input.next').css('display', 'none');
                    currentData && _table.fill(currentData, dataOptions);
                } else {
                    gridData && _table.fill(gridData, dataOptions);
                }
                // column edit bar
                if (!_table.fields && settings.columnEditable) {
                    $(_table).append('<tfoot><tr></tr></tfoot>');
                    var i = 0,l = gridData[0].length + defaultColNum;
                    for (;i<l;i++) {
                        $('<td>')
                        .append(delColButton())
                        .append(insColButton())
                        .appendTo($(_table).find('tfoot tr'));
                    }
                }
            }
            //
            if (settings.log && settings.log.target) {
                $logTable = $(settings.log.target);
                $logTable[0].Actions =  {
                    Full: "<option value='reserve' selected>保留</option>" +
                        "<option value='modify'>修改</option>" + 
                        "<option value='insert'>新增</option>" + 
                        "<option value='delete'>删除</option>"
                    ,Insert: "<option value='insert' selected>新增</option>" + 
                        "<option value='cancel'>撤销</option>"
                    ,Delete: "<option value='reserve'>保留</option>" +
                        "<option value='modify'>修改</option>" +
                        "<option value='delete' selected>删除</option>"
                };
                var LinkGridOptions = {
                    'title': '修改记录'
                    ,'readonly': true
                    ,'draggable': false
                    ,'showFooter': false
                    ,'fields':[
                        {"name":"本轮"}
                        ,{"name":"上轮"}
                        ,{"name":"动作"}
                    ] 
                };
                var LinkGridDataOptions = {
                    beforeCloseRow: function(rownum, data) {
                        if (data[0] == 0) {
                            return '<select>' + $logTable[0].Actions.Delete + '</select>';
                        } else if (data[1] == 0) {
                            return '<select>' + $logTable[0].Actions.Insert + '</select>';
                        } else {
                            return '<select>' + $logTable[0].Actions.Full + '</select>';
                        }
                    }
                };
                $(settings.log.target).Grid(LinkGridOptions, [], LinkGridDataOptions);
                //
                $(settings.log.target)[0].init = function() {
                    var logTable = $(settings.log.target)[0]; 
                    if (!$(logTable).hasClass('executed')) {
                        $(_table).find('tbody tr').each(function(){
                            var seq = $(logTable).find('tbody tr').length;
                            logTable.appendRow([seq+1,seq+1]);
                        });
                        $(logTable).addClass('executed');
                    }
                }
                $logTable.delegate('select', 'focus', function(){
                    $(this).data('oldValue2', $(this).val());
                });
                var delete2 = function(row) {
                    var seq = parseInt(row.children('td.data').eq(0).html());
                    row.children('td.data').eq(0).html(0);
                    $(row).find('select').html($logTable[0].Actions.Delete);
                    row.nextAll().each(function(){
                        var v = $(this).children('td.data').eq(0).html();
                        $(this).children('td.data').eq(0).html(v-1);
                    });
                    $(_table).find('tbody tr').eq(seq - 1).remove();
                };
                var undelete = function(row) {
                    var seq = parseInt(row.children('td.data').eq(1).html());
                    row.children('td.data').eq(0).html(seq);
                    row.nextAll().each(function(){
                        var v = $(this).children('td.data').eq(0).html();
                        $(this).children('td.data').eq(0).html(parseInt(v)+1);
                    });
                    var newValue = $(row).find('select').val();
                    row.find('select').data('oldValue2', '');
                    row.find('select').html($logTable[0].Actions.Full).val(newValue);
                    var row2 = $(_table).find('tbody tr').eq(seq - 1);
                    _table.insertRow(row2);
                };
                var uninsert = function(row) {
                    var seq = parseInt(row.children('td.data').eq(0).html());
                    row.nextAll().each(function(){
                        var v = parseInt($(this).children('td.data').eq(0).html());
                        v > 0 && $(this).children('td.data').eq(0).html(v-1);
                    });
                    row.remove();
                    $(_table).find('tbody tr').eq(seq - 1).remove();
                };
                var changeState = function(row) {
                    var seq = row.children('td.data').eq(0).html(); 
                    var state = row.find('select option:selected').html();
                    $(_table).find('tbody tr').eq(seq - 1)
                    .children('td.data').eq(1).html(state);
                };
                var insert2 = function(row) {
                    if (row) { // add before the row.
                        var currentSeq = row.children('td.data').eq(0).html(); 
                        var newRow = $logTable[0].insertRow(row, [currentSeq, 0]);
                        $(newRow).nextAll().each(function(){
                            var seq = $(this).children('td.data').eq(0).html(); 
                            seq != 0 && $(this).children('td.data').eq(0).html(seq+1);
                        });
                        var oldValue = row.find('select').data('oldValue2');
                        row.find('select').val(oldValue);
                        var newState = $(newRow).find('select option:selected').html();
                        var dataRow = $(_table).find('tbody tr').eq(currentSeq - 1);
                        _table.insertRow(dataRow);
                    } else { // append
                        var r = $logTable.find('tbody tr').last(), seq = 0;
                        while (r.children('td.data').first().html() == 0) {
                            r = r.prev();
                        }
                        r.length == 1 && (seq = parseInt(r.children('td.data').first().html()));
                        $logTable[0].appendRow([seq+1, 0]);
                        _table.appendRow();
                    }
                };
                $logTable.delegate('select', 'change', function() {
                    var oldValue = $(this).data('oldValue2');
                    var newValue = $(this).val();
                    var row = $(this).parents('tr');
                    if ((oldValue == 'reserve' && newValue == 'modify') || 
                    (oldValue == 'modify' && newValue == 'reserve')){
                        changeState(row);
                    } else if ((oldValue == 'reserve' || oldValue == 'modify') && 
                    newValue == 'insert') {
                        insert2(row);
                    } else if ((oldValue == 'reserve' || oldValue == 'modify') 
                    && newValue == 'delete') {
                        delete2(row);
                    } else if (oldValue === 'delete' && (
                    newValue == 'reserve' || newValue == 'modify')) {
                        undelete(row);
                    } else if (oldValue == 'insert' && newValue == 'cancel') {
                        uninsert(row);
                    }
                });
            }
        });
        // 
        return this;
    }
})(jQuery);
