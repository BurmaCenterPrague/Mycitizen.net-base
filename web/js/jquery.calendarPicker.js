// based on CalendarPicker by Roberto Bicchierai http://roberto.open-lab.com/2010/04/06/ultra-light-jquery-calendar/
// MIT license, http://creativecommons.org/licenses/MIT/
// Modified by Christoph Amthor, http://mycitizen.net, to include the time

jQuery.fn.calendarPicker = function(options) {
  // --------------------------  start default option values --------------------------
  if (!options.date) {
    options.date = new Date();
  }

  if (typeof(options.years) == "undefined")
    options.years=1;

  if (typeof(options.months) == "undefined")
    options.months=3;

  if (typeof(options.days) == "undefined")
    options.days=4;

  if (typeof(options.hours) == "undefined")
    options.hours=4;
    
  if (typeof(options.minutes) == "undefined")
    options.minutes=2;
    
  if (typeof(options.showDayArrows) == "undefined")
    options.showDayArrows=true;

  if (typeof(options.useWheel) == "undefined")
    options.useWheel=true;

  if (typeof(options.callbackDelay) == "undefined")
    options.callbackDelay=500;
  
  if (typeof(options.monthNames) == "undefined")
    options.monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

  if (typeof(options.dayNames) == "undefined")
    options.dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];


  // --------------------------  end default option values --------------------------

  var calendar = {currentDate: options.date};
  calendar.options = options;

  //build the calendar on the first element in the set of matched elements.
  var theDiv = this.eq(0);//$(this);
  theDiv.addClass("calBox");

  //empty the div
  theDiv.empty();


  var divYears = $("<div>").addClass("calYear");
  var divMonths = $("<div>").addClass("calMonth");
  var divDays = $("<div>").addClass("calDay");
  var divHours = $("<div>").addClass("calHour");
  var divMinutes = $("<div>").addClass("calMinute");

  theDiv.append(divYears).append(divMonths).append(divDays).append(divHours).append(divMinutes);

  calendar.changeDate = function(date) {
    calendar.currentDate = date;

    var fillYears = function(date) {
      var year = date.getFullYear();
      var t = new Date();
      divYears.empty();
      var nc = options.years*2+1;
      var w = parseInt((theDiv.width()-4-(nc)*4)/nc)+"px";
      for (var i = year - options.years; i <= year + options.years; i++) {
        var d = new Date(date);
        d.setFullYear(i);
        var span = $("<span>").addClass("calElement").attr("millis", d.getTime()).html(i).css("width",w);
        if (d.getYear() == t.getYear())
          span.addClass("today");
        if (d.getYear() == calendar.currentDate.getYear())
          span.addClass("selected");
        divYears.append(span);
      }
    }

    var fillMonths = function(date) {
      var month = date.getMonth();
      var t = new Date();
      divMonths.empty();
      var oldday = date.getDay();
      var nc = options.months*2+1;
      var w = parseInt((theDiv.width()-4-(nc)*4)/nc)+"px";
      for (var i = -options.months; i <= options.months; i++) {
        var d = new Date(date);
        var oldday = d.getDate();
        d.setMonth(month + i);

        if (d.getDate() != oldday) {
          d.setMonth(d.getMonth() - 1);
          d.setDate(28);
        }
        var span = $("<span>").addClass("calElement").attr("millis", d.getTime()).html(options.monthNames[d.getMonth()]).css("width",w);
        if (d.getYear() == t.getYear() && d.getMonth() == t.getMonth())
          span.addClass("today");
        if (d.getYear() == calendar.currentDate.getYear() && d.getMonth() == calendar.currentDate.getMonth())
          span.addClass("selected");
        divMonths.append(span);

      }
    }

    var fillDays = function(date) {
      var day = date.getDate();
      var t = new Date();
      divDays.empty();
      var nc = options.days*2+1;
      var w = parseInt((theDiv.width()-4-(options.showDayArrows?12:0)-(nc)*4)/(nc-(options.showDayArrows?2:0)))+"px";
      for (var i = -options.days; i <= options.days; i++) {
        var d = new Date(date);
        d.setDate(day + i)
        var span = $("<span>").addClass("calElement").attr("millis", d.getTime())
          span.html("<span class=dayNumber>" + d.getDate() + "</span><br>" + options.dayNames[d.getDay()]).css("width",w);
          if (d.getYear() == t.getYear() && d.getMonth() == t.getMonth() && d.getDate() == t.getDate())
            span.addClass("today");
          if (d.getYear() == calendar.currentDate.getYear() && d.getMonth() == calendar.currentDate.getMonth() && d.getDate() == calendar.currentDate.getDate())
            span.addClass("selected");
        divDays.append(span);

      }
    }

    var fillHours = function(date) {
      var hours = date.getHours();
      var t = new Date();
      divHours.empty();
      var nc = options.hours*2+1;
      var w = parseInt((theDiv.width()-4-(options.showDayArrows?12:0)-(nc)*4)/(nc-(options.showDayArrows?2:0)))+"px";
      for (var i = -options.hours; i <= options.hours; i++) {
        var d = new Date(date);
        d.setHours(hours + i)
        var span = $("<span>").addClass("calElement").attr("millis", d.getTime())
          span.html("<span class=hourNumber>" + d.getHours() + " h</span>").css("width",w);
          if (d.getHours() == t.getHours())
            span.addClass("today");
          if (d.getHours() == calendar.currentDate.getHours())
            span.addClass("selected");
        divHours.append(span);

      }
    }


    var fillMinutes = function(date) {
      var minutes = 15 * parseInt(date.getMinutes() / 15);
      var t = new Date();
      divMinutes.empty();
      var nc = options.minutes*2+1;
      var w = parseInt((theDiv.width()-4-(options.showDayArrows?12:0)-(nc)*4)/(nc-(options.showDayArrows?2:0)))+"px";
      for (var i = -options.minutes; i <= options.minutes; i++) {
        var d = new Date(date);
        d.setMinutes(minutes + 15*i)
        var span = $("<span>").addClass("calElement").attr("millis", d.getTime())
          span.html('<span class=minuteNumber>' + d.getMinutes() + " min</span>").css("width",w);
          if (d.getMinutes() == 15 * parseInt(t.getMinutes() / 15))
            span.addClass("today");
          if (d.getMinutes() == 15 * parseInt(calendar.currentDate.getMinutes() / 15))
            span.addClass("selected");
        divMinutes.append(span);

      }
    }

    var deferredCallBack = function() {
      if (typeof(options.callback) == "function") {
        if (calendar.timer)
          clearTimeout(calendar.timer);

        calendar.timer = setTimeout(function() {
          options.callback(calendar);
        }, options.callbackDelay);
      }
    }


    fillYears(date);
    fillMonths(date);
    fillDays(date);
    fillHours(date);
    fillMinutes(date);

    deferredCallBack();

  }

  theDiv.click(function(ev) {
    var el = $(ev.target).closest(".calElement");
    if (el.hasClass("calElement")) {
      calendar.changeDate(new Date(parseInt(el.attr("millis"))));
    }
  });


  //if mousewheel
  if ($.event.special.mousewheel && options.useWheel) {
    divYears.mousewheel(function(event, delta) {
      var d = new Date(calendar.currentDate.getTime());
      d.setFullYear(d.getFullYear() + delta);
      calendar.changeDate(d);
      return false;
    });
    divMonths.mousewheel(function(event, delta) {
      var d = new Date(calendar.currentDate.getTime());
      d.setMonth(d.getMonth() + delta);
      calendar.changeDate(d);
      return false;
    });
    divDays.mousewheel(function(event, delta) {
      var d = new Date(calendar.currentDate.getTime());
      d.setDate(d.getDate() + delta);
      calendar.changeDate(d);
      return false;
    });
    divHours.mousewheel(function(event, delta) {
      var d = new Date(calendar.currentDate.getTime());
      d.setHours(d.getHours() + delta);
      calendar.changeHours(d);
      return false;
    });
    divMinutes.mousewheel(function(event, delta) {
      var d = new Date(calendar.currentDate.getTime());
      d.setMinutes(d.getMinutes() + delta);
      calendar.changeMinutes(d);
      return false;
    });

  }


  calendar.changeDate(options.date);

  return calendar;
};