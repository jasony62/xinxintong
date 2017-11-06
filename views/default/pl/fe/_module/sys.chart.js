'use strict';
angular.module('sys.chart', []).
provider('srvChart', function() {
    var _oConfig;
    this.config = function(oConfig) {
        _oConfig = oConfig;
    };
    this.$get = [function() {
        return {
            config: function(oConfig) {
                _oConfig = oConfig;
            },
            drawBarChart: function(item) {
                var categories = [],
                    series = [];

                item.ops.forEach(function(op) {
                    categories.push(op.l);
                    series.push(parseInt(op.c));
                });
                new Highcharts.Chart({
                    chart: {
                        type: 'bar',
                        renderTo: item.id
                    },
                    title: {
                        text: '' //item.title
                    },
                    legend: {
                        enabled: false
                    },
                    xAxis: {
                        categories: categories
                    },
                    yAxis: {
                        'title': '',
                        allowDecimals: false,
                        labels: {
                            formatter: function() {
                                if (_oConfig && _oConfig.label === 'percentage') {
                                    return new Number(this.value / item.sum * 100).toFixed(0) + '%';
                                } else {
                                    return this.value;
                                }
                            }
                        },
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: {
                                enabled: true,
                                inside: true,
                                formatter: function() {
                                    if (_oConfig && _oConfig.label === 'percentage') {
                                        return new Number(this.y / item.sum * 100).toFixed(2) + '%'
                                    } else {
                                        return this.y;
                                    }
                                },
                            }
                        }
                    },
                    tooltip: {
                        pointFormatter: function() {
                            if (_oConfig && _oConfig.label === 'percentage') {
                                return '占比:' + new Number(this.y / item.sum * 100).toFixed(2) + '%';
                            } else {
                                return this.series.name + ':' + this.y;
                            }
                        }
                    },
                    series: [{
                        name: '数量',
                        data: series
                    }],
                    lang: {
                        downloadJPEG: "下载JPEG 图片",
                        downloadPDF: "下载PDF文档",
                        downloadPNG: "下载PNG 图片",
                        downloadSVG: "下载SVG 矢量图",
                        printChart: "打印图片",
                        exportButtonTitle: "导出图片"
                    }
                });
            },
            drawPieChart: function(item) {
                var categories = [],
                    series = [];

                item.ops.forEach(function(op) {
                    series.push({
                        name: op.l,
                        y: parseInt(op.c),
                        p: op.p
                    });
                });
                new Highcharts.Chart({
                    chart: {
                        type: 'pie',
                        renderTo: item.id
                    },
                    title: {
                        text: '' //item.title
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                formatter: function() {
                                    if (_oConfig && _oConfig.label === 'percentage') {
                                        return '<b>' + this.point.name + '</b>:' + new Number(this.percentage).toFixed(2) + '%';
                                    } else {
                                        return '<b>' + this.point.name + '</b>:' + this.y;
                                    }
                                },
                                style: {
                                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                                }
                            }
                        }
                    },
                    tooltip: {
                        pointFormatter: function() {
                            if (_oConfig && _oConfig.label === 'percentage') {
                                return '占比:' + new Number(this.percentage).toFixed(2) + '%';
                            } else {
                                return this.series.name + ':' + this.y;
                            }
                        }
                    },
                    series: [{
                        name: '数量',
                        data: series
                    }],
                    lang: {
                        downloadJPEG: "下载JPEG 图片",
                        downloadPDF: "下载PDF文档",
                        downloadPNG: "下载PNG 图片",
                        downloadSVG: "下载SVG 矢量图",
                        printChart: "打印图片",
                        exportButtonTitle: "导出图片"
                    }
                });
            },
            drawNumPie: function(item, schema) {
                var categories = [],
                    series = [],
                    sum = 0,
                    otherSum;
                item.records.forEach(function(record) {
                    var recVal = record.data[schema.id] ? parseInt(record.data[schema.id]) : 0;
                    sum += recVal;
                    series.push({
                        name: recVal,
                        y: recVal
                    });
                });
                otherSum = parseInt(item.sum) - sum;
                if (otherSum != 0) {
                    series.push({ name: '其它', y: otherSum });
                }

                new Highcharts.Chart({
                    chart: {
                        type: 'pie',
                        renderTo: schema.id
                    },
                    title: {
                        text: '' //schema.title
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                                style: {
                                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                                }
                            }
                        }
                    },
                    series: [{
                        name: '所占百分比',
                        data: series
                    }],
                    lang: {
                        downloadJPEG: "下载JPEG 图片",
                        downloadPDF: "下载PDF文档",
                        downloadPNG: "下载PNG 图片",
                        downloadSVG: "下载SVG 矢量图",
                        printChart: "打印图片",
                        exportButtonTitle: "导出图片"
                    }
                });
            },
            drawLineChart: function(item) {
                var categories = [],
                    data = [];

                item.ops.forEach(function(op) {
                    categories.push(op.l);
                    data.push(op.c);
                });
                new Highcharts.Chart({
                    chart: {
                        type: 'line',
                        renderTo: item.id
                    },
                    title: {
                        text: '' //item.title,
                    },
                    xAxis: {
                        categories: categories
                    },
                    yAxis: {
                        title: {
                            text: '平均分'
                        },
                        plotLines: [{
                            value: 0,
                            width: 1,
                            color: '#808080'
                        }]
                    },
                    series: [{
                        name: item.title,
                        data: data
                    }],
                    lang: {
                        downloadJPEG: "下载JPEG 图片",
                        downloadPDF: "下载PDF文档",
                        downloadPNG: "下载PNG 图片",
                        downloadSVG: "下载SVG 矢量图",
                        printChart: "打印图片",
                        exportButtonTitle: "导出图片"
                    }
                });
            }
        }
    }];
});