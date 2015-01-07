/*
 Author URI: http://indianbendsolutions.com
 License: GPL
 
 GPL License: http://www.opensource.org/licenses/gpl-license.php
 
 This program is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
function IBS_GCAL_EVENTS($, args, mode) {
    this.init(args, mode)
}
(function ($) {
    IBS_GCAL_EVENTS.prototype.init = function (args, mode) {
        var list = this;
        this.options = {
            calendar: 'en.usa#holiday@group.v.calendar.google.com', //Google public holidays feed
            apiKey: 'AIzaSyDU0aiNYlY1sRHPuZadvnfAkIRMhEFobP4', // see href="https://developers.google.com/api-client-library/python/guide/aaa_apikeys
            dateFormat: 'ddd MMM DD',
            timeFormat: 'h:mm a',
            errorMsg: 'No events in calendar',
            max: 100,
            start: 'now',
            descending: false
        }
        for (var arg in args) {
            if (typeof this.options[arg] !== 'undefined' && args[arg] !== '') {
                this.options[arg] = args[arg];
            }
        }
        this.qtip_params = function (event) {
            var loc = '';
            if (typeof event.location !== 'undefined' && event.location !== '') {
                loc = '<p>' + 'Location: ' + event.location + '</p>';
            }
            var desc = '';
            if (typeof event.description !== 'undefined' && event.description !== '') {
                desc = '<p>' + event.description + '</p>'
            }
            var time = moment(event.start).format(list.options.dateFormat + ' ' + list.options.timeFormat) + moment(event.end).format(' - ' + list.options.timeFormat);
            if (event.allDay) {
                time = 'All day';
            }
            return {
                content: {'text': '<p>' + event.title + '</p>' + loc + desc + '<p>' + time + '</p>'},
                position: {
                    my: 'bottom center',
                    at: 'top center'
                },
                style: {
                    classes: args['qtip']['style'] + ' ' + args['qtip']['rounded'] + args['qtip']['shadow']

                },
                show: {
                    event: 'mouseover'
                },
                hide: {
                    event: 'mouseout mouseleave'
                }
            };
        }
        var feedUrl = 'https://www.googleapis.com/calendar/v3/calendars/' +
                encodeURIComponent(this.options.calendar.trim()) + '/events?key=' + this.options.apiKey +
                '&orderBy=startTime&singleEvents=true';
        if (this.options.start === 'now')
            this.options.start = moment().format('YYYY-MM-DD');
        feedUrl += '&timeMin=' + new Date(this.options.start).toISOString();
        $.getJSON(feedUrl)
                .then(
                        function (data) {
                            if (list.options.descending) {
                                data.items = data.items.reverse();
                            }
                            data.items = data.items.slice(0, list.options.max);
                            var events = [];
                            $.each(data.items, function (e, item) {
                                var event = {
                                    id: e,
                                    title: item.summary || '',
                                    start: moment(item.start.dateTime || item.start.date || ''),
                                    end: moment(item.end.dateTime || item.end.date || ''),
                                    location: typeof item.location === 'undefined' ? '' : item.location,
                                    description: typeof item.description === 'undefined' ? '' : item.description,
                                    url: typeof item.htmlLink === 'undefined' ? '' : item.htmlLink
                                };
                                events.push(event);
                            });
                            if(events.length === 0){
                                events.push( {
                                    title: 'No events found',
                                    start: moment(),
                                    end: moment(),
                                    location: '',
                                    description: '',
                                    url: ''
                                });
                            }
                            if (mode === 'shortcode') {
                                var event_div = '#ibs-gcal-events-' + args.id;
                                $(event_div).empty().css('cursor', 'pointer');
                                for (var i = 0; i < events.length; i++) {
                                    var pattern = args.dateFormat
                                    var d = moment(events[i].start).format(pattern);
                                    var f = moment(events[i].start).format(args.timeFormat);
                                    var t = moment(events[i].end).format(args.timeFormat);
                                    $(event_div)
                                            .append($('<div>')
                                                    .append($('<div>').addClass('bar')
                                                            .append($('<a>').attr({href: events[i].url, target:'_blank'}).text(events[i].title).css('padding', '3px')))//text(events[i].title).addClass('bar'))
                                                    .append($('<div>').addClass('when-div')
                                                            .append($('<span>').text(d))
                                                            .append($('<span>').text(f))
                                                            .append($('<span>').text('to'))
                                                            .append($('<span>').text(t)))
                                                    .append($('<div>').text(events[i].location).addClass('where-div'))
                                                    .append($('<div>').css('display', events[i].description === '' ? 'none' : 'block')
                                                            .append($('<div>').html(events[i].description).addClass('textbox')))
                                                    );
                                }
                            } else {
                                var event_table = '#ibs-wgcal-events-' + args.id;
                                for (var i = 0; i < events.length; i++) {
                                    var qtp = list.qtip_params(events[i]);
                                    $(event_table)
                                            .append($('<div>').qtip(qtp)
                                                    .append($('<a>').attr({href: events[i].url, target:'_blank'}).text(events[i].title).css('padding', '3px')));
                                }
                            }
                        },
                        function () {
                            console.log("Get Widget Google Events failed.");
                            $('#ibs-gcal-events-' + args.id).html('<p><strong>' + list.options.errorMsg + '</strong></p>');
                        }
                );
    };
}(jQuery));