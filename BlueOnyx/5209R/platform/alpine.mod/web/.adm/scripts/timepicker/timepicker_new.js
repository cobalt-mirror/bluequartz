/* jQuery timepicker
 * Replaces a single text input with a set of select boxes for hour, minute, and am/pm (optional)
 *
 * Original copyright (c) 2007 Jason Huck/Core Five Creative (http://www.corefive.com/)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) 
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Improved by Anuj Dutta (@andhapp) by adding specs and refactoring the code to remove duplication 
 * and improve code standards
 *
 * Version 1.1.1
 *
 */

;(function($){	
	$.fn.timepicker = function(user_options) {
		
    var elem = this
    var CSS_CLASS = 'timepicker'
    var PARENT_CONTAINER_ID = 'timepicker'
    var ELEMENT_CSS_CLASS = 'timepickerElement'

		return this.each(function() {
			var options = $.extend({}, $.timepicker.selectableValues, $.timepicker, user_options)

      var timePicker = new TimePicker(elem, options)
      var htmlResult = timePicker.process()

      if(options.inPopup) {
        var popUpContainer = createPopupContainer()
        popUpContainer.append(htmlResult)

        elem.focusin(function() { focusInHandler(elem, popUpContainer) })
      } else {
        makeHidden(this) // Explicity setting the type property
        elem.parent().append(htmlResult)
      }

      timePicker.handleEvents()
			
		})

    function focusInHandler(elem, object) {
      setPosition(elem, object)

      object.fadeIn(400, function() {
        $(document).bind('click', function(event) {
          if(canHidePopupContainer(event)) {
            object.hide()
            $(document).unbind('click')
          }
        })
      })

    }

    function canHidePopupContainer(event) {
      function isPopupContainer(event) {
        return $(event.target).attr('class') === CSS_CLASS
      }

      function isInputBox(event) {
        return $(event.target)[0] === elem[0]
      }

      function isTimepickerDropdowns(event) {
        return $(event.target).attr('class') && $(event.target).attr('class').indexOf(ELEMENT_CSS_CLASS) > 0
      }

      return (
        ! ( isPopupContainer(event) ||
            isInputBox(event) ||
            isTimepickerDropdowns(event))
      )
    }

    function createPopupContainer() {

      var popUpContainer = {}

      if ($("#" + PARENT_CONTAINER_ID).length > 0) {
        popUpContainer = $('#' + PARENT_CONTAINER_ID)
      } else {
        popUpContainer = $('<div>', {id: PARENT_CONTAINER_ID, style:'display:none; position:absolute;', 'class': CSS_CLASS})
      }

      $('body').append(popUpContainer)

      return setPosition(elem, popUpContainer)
    }

    function setPosition(source, object) {
      var top = source.offset().top + source.outerHeight()
      var left = source.offset().left
      object.css({top: top, left: left})
      return object
    }

    function makeHidden(object) {
      object.type = "hidden"
    }
		
	}

  function TimePicker(elem, options) {
    this.element = elem
    this.options = options

    this.handleEvents = function() {
      var self = this
      $('.timepickerElement').change(function() {
        var final_value = self.hours.selectedValue() + ':' + self.minutes.selectedValue()

        if(self.ampm) {
          final_value += self.ampm.selectedValue()
        }

        self.element.val(final_value)
      })
    }

    this.process = function() {
      var result = ''
      var id = this.element.attr('id')

      if(this.options.clock12) {
        this.hours = new Hours(id, this.options.hrs12)
        this.ampm = new AmPm(id, this.options.ap)
      } else {
        this.hours = new Hours(id, this.options.hrs24)
      }

      this.minutes = new Minutes(id, this.options.mins)

      result += this.hours.createDOMElementHTML()
      result += this.minutes.createDOMElementHTML()

      if(this.ampm) {
        result += this.ampm.createDOMElementHTML()
      }

      return result
    }

    function createSelectElementHTML(id, className, source) {
      var source = source.split(',')
      var result = '<select id="' + className + '_' + id + '" class="' + className + ' timepickerElement">'

      $.each(source, function(index, value) {
        result += '<option value="' + value + '"'
        result += '>' + value + '</option>'
      })
      result += '</select>'

      return result;
    }

    function Hours(id, value) {
      this.id = id
      this.value = value
      this.className = 'h'

      this.createDOMElementHTML = function() {
        return createSelectElementHTML(this.id, this.className, this.value)
      }
      this.domElement = function() {
        return $('#' + this.className + '_' + this.id)
      }
      this.selectedValue = function() {
        return this.domElement().attr('value')
      }
    }

    function Minutes(id, value) {
      this.id = id
      this.value = value
      this.className = 'm'

      this.createDOMElementHTML = function() {
        return createSelectElementHTML(this.id, this.className, this.value)

      }
      this.domElement = function() {
        return $('#' + this.className + '_' + this.id)
      }
      this.selectedValue = function() {
        return this.domElement().attr('value')
      }
    }

    function AmPm(id, value) {
      this.id = id
      this.value = value
      this.className = 'p'

      this.createDOMElementHTML = function() {
        return createSelectElementHTML(this.id, this.className, this.value)
      }
      this.domElement = function() {
        return $('#' + this.className + '_' + this.id)
      }
      this.selectedValue = function() {
        return this.domElement().attr('value')
      }
    }

  }

  $.timepicker = {
		selectableValues: {
			ap: 		"am,pm",
			mins: 	"00,15,30,45",
			hrs12: 	"01,02,03,04,05,06,07,08,09,10,11,12",
			hrs24: 	"00,01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16,17,18,19,20,21,22,23",
		},
		clock12: true,
		inPopup: false
	}

})(jQuery)

