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
        var options = {
            calendar: 'en.usa#holiday@group.v.calendar.google.com', //Google public holidays feed
            apiKey: 'AIzaSyDU0aiNYlY1sRHPuZadvnfAkIRMhEFobP4', // see href="https://developers.google.com/api-client-library/python/guide/aaa_apikeys
            dateFormat: 'ddd MMM DD',
            timeFormat: 'h:mm a',
            errorMsg: 'No events in calendar',
            max: 100,
            start: 'now',
            descending: false
        }
        for (arg in args) {
            var data = args[arg];
            if (typeof data === 'string') {
                data = data.toLowerCase();
                if (data === 'yes' || data === 'no') {
                    args[arg] = data === 'yes' ? true : false;
                } else {
                    if (data === 'true' || data === 'false') {
                        args[arg] = data === 'true' ? true : false;
                    }
                }
            }
        }
        for (var arg in args) {
            if (typeof options[arg] !== 'undefined' && args[arg] !== '') {
                options[arg] = args[arg];
            }
        }
        gcal_qtip_params = function (event) {
            var bg = '<p style="background-color:'
                    + event.color
                    + '; color:'
                    + event.textColor
                    + ';" >';
            bg = '<p style="background-color:silver; color: black;" >';
            var loc = '';
            if (typeof event.location !== 'undefined' && event.location !== '') {
                loc = '<p>' + 'Location: ' + event.location + '</p>';
            }
            var desc = '';
            if (typeof event.description !== 'undefined' && event.description !== '') {
                desc = '<p>' + event.description + '</p>'
            }
            var time = moment(event.start).format(options.dateFormat + ' ' + options.timeFormat) + moment(event.end).format(' - ' + options.timeFormat);
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
        widget_list = function () {


        };
        var feedUrl = 'https://www.googleapis.com/calendar/v3/calendars/' +
                encodeURIComponent(options.calendar.trim()) + '/events?key=' + options.apiKey +
                '&orderBy=startTime&singleEvents=true';
        if (options.start === 'now')
            options.start = moment().format('YYYY-MM-DD');
        feedUrl += '&timeMin=' + new Date(options.start).toISOString();
        $.getJSON(feedUrl)
                .then(
                        function (data) {
                            if (options.descending) {
                                data.items = data.items.reverse();
                            }
                            data.items = data.items.slice(0, options.max);
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
                            if (mode === 'shortcode') {
                                var event_div = '#ibs-gcal-events-' + args.id;
                                $(event_div).empty().css('cursor', 'pointer');
                                for (var i = 0; i < events.length; i++) {
                                    var pattern = args.dateFormat
                                    var d = moment(events[i].start).format(pattern);
                                    var f = moment(events[i].start).format(args.timeFormat);
                                    var t = moment(events[i].end).format(args.timeFormat);
                                    var qtp = gcal_qtip_params(events[i]);
                                    $(event_div)
                                            .append($('<div>')
                                                    .append($('<div>').addClass('bar').qtip(qtp)
                                                            .append($('<a>').attr({href: events[i].url}).text(events[i].title).css('padding', '3px')))//text(events[i].title).addClass('bar'))
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
                                    qtp = gcal_qtip_params(events[i]);
                                    $(event_table)
                                            .append($('<div>').qtip(qtp)
                                                    .append($('<a>').attr({href: events[i].url}).text(events[i].title).css('padding', '3px')));
                                }
                            }
                        },
                        function () {
                            console.log("Get Widget Google Events failed.");
                            $('#ibs-gcal-events-' + args.id).html('<p><strong>' + options.errorMsg + '</strong></p>');
                        }
                );
    };
}(jQuery));