(function(factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD (Register as an anonymous module)
    define(['jquery'], factory);
  } else if (typeof exports === 'object') {
    // Node/CommonJS
    module.exports = factory(require('jquery'));
  } else {
    // Browser globals
    factory(jQuery);
  }
}(function($) {

  var pluses = /\+/g;

  function encode(s) {
    return config.raw ? s : encodeURIComponent(s);
  }

  function decode(s) {
    return config.raw ? s : decodeURIComponent(s);
  }

  function stringifyCookieValue(value) {
    return encode(config.json ? JSON.stringify(value) : String(value));
  }

  function parseCookieValue(s) {
    if (s.indexOf('"') === 0) {
      // This is a quoted cookie as according to RFC2068, unescape...
      s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    }

    try {
      // Replace server-side written pluses with spaces.
      // If we can't decode the cookie, ignore it, it's unusable.
      // If we can't parse the cookie, ignore it, it's unusable.
      s = decodeURIComponent(s.replace(pluses, ' '));
      return config.json ? JSON.parse(s) : s;
    } catch (e) {}
  }

  function read(s, converter) {
    var value = config.raw ? s : parseCookieValue(s);
    return $.isFunction(converter) ? converter(value) : value;
  }

  var config = $.cookie = function(key, value, options) {

    // Write

    if (arguments.length > 1 && !$.isFunction(value)) {
      options = $.extend({}, config.defaults, options);

      if (typeof options.expires === 'number') {
        var days = options.expires,
          t = options.expires = new Date();
        t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
      }

      return (document.cookie = [
        encode(key), '=', stringifyCookieValue(value),
        options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
        options.path ? '; path=' + options.path : '',
        options.domain ? '; domain=' + options.domain : '',
        options.secure ? '; secure' : ''
      ].join(''));
    }

    // Read

    var result = key ? undefined : {},
      // To prevent the for loop in the first place assign an empty array
      // in case there are no cookies at all. Also prevents odd result when
      // calling $.cookie().
      cookies = document.cookie ? document.cookie.split('; ') : [],
      i = 0,
      l = cookies.length;

    for (; i < l; i++) {
      var parts = cookies[i].split('='),
        name = decode(parts.shift()),
        cookie = parts.join('=');

      if (key === name) {
        // If second argument (value) is a function it's a converter...
        result = read(cookie, value);
        break;
      }

      // Prevent storing a cookie that we couldn't decode.
      if (!key && (cookie = read(cookie)) !== undefined) {
        result[name] = cookie;
      }
    }

    return result;
  };

  config.defaults = {};

  $.removeCookie = function(key, options) {
    // Must not alter options, thus extending a fresh object...
    $.cookie(key, '', $.extend({}, options, {
      expires: -1
    }));
    return !$.cookie(key);
  };

}));

(function defineMustache(global, factory) {
  if (typeof exports === 'object' && exports && typeof exports.nodeName !== 'string') {
    factory(exports); // CommonJS
  } else if (typeof define === 'function' && define.amd) {
    define(['exports'], factory); // AMD
  } else {
    global.Mustache = {};
    factory(global.Mustache); // script, wsh, asp
  }
}(this, function mustacheFactory(mustache) {

  var objectToString = Object.prototype.toString;
  var isArray = Array.isArray || function isArrayPolyfill(object) {
    return objectToString.call(object) === '[object Array]';
  };

  function isFunction(object) {
    return typeof object === 'function';
  }

  /**
   * More correct typeof string handling array
   * which normally returns typeof 'object'
   */
  function typeStr(obj) {
    return isArray(obj) ? 'array' : typeof obj;
  }

  function escapeRegExp(string) {
    return string.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&');
  }

  /**
   * Null safe way of checking whether or not an object,
   * including its prototype, has a given property
   */
  function hasProperty(obj, propName) {
    return obj != null && typeof obj === 'object' && (propName in obj);
  }

  // Workaround for https://issues.apache.org/jira/browse/COUCHDB-577
  // See https://github.com/janl/mustache.js/issues/189
  var regExpTest = RegExp.prototype.test;

  function testRegExp(re, string) {
    return regExpTest.call(re, string);
  }

  var nonSpaceRe = /\S/;

  function isWhitespace(string) {
    return !testRegExp(nonSpaceRe, string);
  }

  var entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#x60;',
    '=': '&#x3D;'
  };

  function escapeHtml(string) {
    return String(string).replace(/[&<>"'`=\/]/g, function fromEntityMap(s) {
      return entityMap[s];
    });
  }

  var whiteRe = /\s*/;
  var spaceRe = /\s+/;
  var equalsRe = /\s*=/;
  var curlyRe = /\s*\}/;
  var tagRe = /#|\^|\/|>|\{|&|=|!/;

  function parseTemplate(template, tags) {
    if (!template)
      return [];

    var sections = []; // Stack to hold section tokens
    var tokens = []; // Buffer to hold the tokens
    var spaces = []; // Indices of whitespace tokens on the current line
    var hasTag = false; // Is there a {{tag}} on the current line?
    var nonSpace = false; // Is there a non-space char on the current line?

    // Strips all whitespace tokens array for the current line
    // if there was a {{#tag}} on it and otherwise only space.
    function stripSpace() {
      if (hasTag && !nonSpace) {
        while (spaces.length)
          delete tokens[spaces.pop()];
      } else {
        spaces = [];
      }

      hasTag = false;
      nonSpace = false;
    }

    var openingTagRe, closingTagRe, closingCurlyRe;

    function compileTags(tagsToCompile) {
      if (typeof tagsToCompile === 'string')
        tagsToCompile = tagsToCompile.split(spaceRe, 2);

      if (!isArray(tagsToCompile) || tagsToCompile.length !== 2)
        throw new Error('Invalid tags: ' + tagsToCompile);

      openingTagRe = new RegExp(escapeRegExp(tagsToCompile[0]) + '\\s*');
      closingTagRe = new RegExp('\\s*' + escapeRegExp(tagsToCompile[1]));
      closingCurlyRe = new RegExp('\\s*' + escapeRegExp('}' + tagsToCompile[1]));
    }

    compileTags(tags || mustache.tags);

    var scanner = new Scanner(template);

    var start, type, value, chr, token, openSection;
    while (!scanner.eos()) {
      start = scanner.pos;

      // Match any text between tags.
      value = scanner.scanUntil(openingTagRe);

      if (value) {
        for (var i = 0, valueLength = value.length; i < valueLength; ++i) {
          chr = value.charAt(i);

          if (isWhitespace(chr)) {
            spaces.push(tokens.length);
          } else {
            nonSpace = true;
          }

          tokens.push(['text', chr, start, start + 1]);
          start += 1;

          // Check for whitespace on the current line.
          if (chr === '\n')
            stripSpace();
        }
      }

      // Match the opening tag.
      if (!scanner.scan(openingTagRe))
        break;

      hasTag = true;

      // Get the tag type.
      type = scanner.scan(tagRe) || 'name';
      scanner.scan(whiteRe);

      // Get the tag value.
      if (type === '=') {
        value = scanner.scanUntil(equalsRe);
        scanner.scan(equalsRe);
        scanner.scanUntil(closingTagRe);
      } else if (type === '{') {
        value = scanner.scanUntil(closingCurlyRe);
        scanner.scan(curlyRe);
        scanner.scanUntil(closingTagRe);
        type = '&';
      } else {
        value = scanner.scanUntil(closingTagRe);
      }

      // Match the closing tag.
      if (!scanner.scan(closingTagRe))
        throw new Error('Unclosed tag at ' + scanner.pos);

      token = [type, value, start, scanner.pos];
      tokens.push(token);

      if (type === '#' || type === '^') {
        sections.push(token);
      } else if (type === '/') {
        // Check section nesting.
        openSection = sections.pop();

        if (!openSection)
          throw new Error('Unopened section "' + value + '" at ' + start);

        if (openSection[1] !== value)
          throw new Error('Unclosed section "' + openSection[1] + '" at ' + start);
      } else if (type === 'name' || type === '{' || type === '&') {
        nonSpace = true;
      } else if (type === '=') {
        // Set the tags for the next time around.
        compileTags(value);
      }
    }

    // Make sure there are no open sections when we're done.
    openSection = sections.pop();

    if (openSection)
      throw new Error('Unclosed section "' + openSection[1] + '" at ' + scanner.pos);

    return nestTokens(squashTokens(tokens));
  }

  /**
   * Combines the values of consecutive text tokens in the given `tokens` array
   * to a single token.
   */
  function squashTokens(tokens) {
    var squashedTokens = [];

    var token, lastToken;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      token = tokens[i];

      if (token) {
        if (token[0] === 'text' && lastToken && lastToken[0] === 'text') {
          lastToken[1] += token[1];
          lastToken[3] = token[3];
        } else {
          squashedTokens.push(token);
          lastToken = token;
        }
      }
    }

    return squashedTokens;
  }

  function nestTokens(tokens) {
    var nestedTokens = [];
    var collector = nestedTokens;
    var sections = [];

    var token, section;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      token = tokens[i];

      switch (token[0]) {
        case '#':
        case '^':
          collector.push(token);
          sections.push(token);
          collector = token[4] = [];
          break;
        case '/':
          section = sections.pop();
          section[5] = token[2];
          collector = sections.length > 0 ? sections[sections.length - 1][4] : nestedTokens;
          break;
        default:
          collector.push(token);
      }
    }

    return nestedTokens;
  }

  /**
   * A simple string scanner that is used by the template parser to find
   * tokens in template strings.
   */
  function Scanner(string) {
    this.string = string;
    this.tail = string;
    this.pos = 0;
  }

  /**
   * Returns `true` if the tail is empty (end of string).
   */
  Scanner.prototype.eos = function eos() {
    return this.tail === '';
  };

  /**
   * Tries to match the given regular expression at the current position.
   * Returns the matched text if it can match, the empty string otherwise.
   */
  Scanner.prototype.scan = function scan(re) {
    var match = this.tail.match(re);

    if (!match || match.index !== 0)
      return '';

    var string = match[0];

    this.tail = this.tail.substring(string.length);
    this.pos += string.length;

    return string;
  };

  /**
   * Skips all text until the given regular expression can be matched. Returns
   * the skipped string, which is the entire tail if no match can be made.
   */
  Scanner.prototype.scanUntil = function scanUntil(re) {
    var index = this.tail.search(re),
      match;

    switch (index) {
      case -1:
        match = this.tail;
        this.tail = '';
        break;
      case 0:
        match = '';
        break;
      default:
        match = this.tail.substring(0, index);
        this.tail = this.tail.substring(index);
    }

    this.pos += match.length;

    return match;
  };

  /**
   * Represents a rendering context by wrapping a view object and
   * maintaining a reference to the parent context.
   */
  function Context(view, parentContext) {
    this.view = view;
    this.cache = {
      '.': this.view
    };
    this.parent = parentContext;
  }

  /**
   * Creates a new context using the given view with this context
   * as the parent.
   */
  Context.prototype.push = function push(view) {
    return new Context(view, this);
  };

  /**
   * Returns the value of the given name in this context, traversing
   * up the context hierarchy if the value is absent in this context's view.
   */
  Context.prototype.lookup = function lookup(name) {
    var cache = this.cache;

    var value;
    if (cache.hasOwnProperty(name)) {
      value = cache[name];
    } else {
      var context = this,
        names, index, lookupHit = false;

      while (context) {
        if (name.indexOf('.') > 0) {
          value = context.view;
          names = name.split('.');
          index = 0;

          while (value != null && index < names.length) {
            if (index === names.length - 1)
              lookupHit = hasProperty(value, names[index]);

            value = value[names[index++]];
          }
        } else {
          value = context.view[name];
          lookupHit = hasProperty(context.view, name);
        }

        if (lookupHit)
          break;

        context = context.parent;
      }

      cache[name] = value;
    }

    if (isFunction(value))
      value = value.call(this.view);

    return value;
  };

  function Writer() {
    this.cache = {};
  }

  /**
   * Clears all cached templates in this writer.
   */
  Writer.prototype.clearCache = function clearCache() {
    this.cache = {};
  };

  /**
   * Parses and caches the given `template` and returns the array of tokens
   * that is generated from the parse.
   */
  Writer.prototype.parse = function parse(template, tags) {
    var cache = this.cache;
    var tokens = cache[template];

    if (tokens == null)
      tokens = cache[template + ':' + (tags || mustache.tags).join(':')] = parseTemplate(template, tags);

    return tokens;
  };


  Writer.prototype.render = function render(template, view, partials) {
    var tokens = this.parse(template);
    var context = (view instanceof Context) ? view : new Context(view);
    return this.renderTokens(tokens, context, partials, template);
  };

  Writer.prototype.renderTokens = function renderTokens(tokens, context, partials, originalTemplate) {
    var buffer = '';

    var token, symbol, value;
    for (var i = 0, numTokens = tokens.length; i < numTokens; ++i) {
      value = undefined;
      token = tokens[i];
      symbol = token[0];

      if (symbol === '#') value = this.renderSection(token, context, partials, originalTemplate);
      else if (symbol === '^') value = this.renderInverted(token, context, partials, originalTemplate);
      else if (symbol === '>') value = this.renderPartial(token, context, partials, originalTemplate);
      else if (symbol === '&') value = this.unescapedValue(token, context);
      else if (symbol === 'name') value = this.escapedValue(token, context);
      else if (symbol === 'text') value = this.rawValue(token);

      if (value !== undefined)
        buffer += value;
    }

    return buffer;
  };

  Writer.prototype.renderSection = function renderSection(token, context, partials, originalTemplate) {
    var self = this;
    var buffer = '';
    var value = context.lookup(token[1]);

    function subRender(template) {
      return self.render(template, context, partials);
    }

    if (!value) return;

    if (isArray(value)) {
      for (var j = 0, valueLength = value.length; j < valueLength; ++j) {
        buffer += this.renderTokens(token[4], context.push(value[j]), partials, originalTemplate);
      }
    } else if (typeof value === 'object' || typeof value === 'string' || typeof value === 'number') {
      buffer += this.renderTokens(token[4], context.push(value), partials, originalTemplate);
    } else if (isFunction(value)) {
      if (typeof originalTemplate !== 'string')
        throw new Error('Cannot use higher-order sections without the original template');

      value = value.call(context.view, originalTemplate.slice(token[3], token[5]), subRender);

      if (value != null)
        buffer += value;
    } else {
      buffer += this.renderTokens(token[4], context, partials, originalTemplate);
    }
    return buffer;
  };

  Writer.prototype.renderInverted = function renderInverted(token, context, partials, originalTemplate) {
    var value = context.lookup(token[1]);

    if (!value || (isArray(value) && value.length === 0))
      return this.renderTokens(token[4], context, partials, originalTemplate);
  };

  Writer.prototype.renderPartial = function renderPartial(token, context, partials) {
    if (!partials) return;

    var value = isFunction(partials) ? partials(token[1]) : partials[token[1]];
    if (value != null)
      return this.renderTokens(this.parse(value), context, partials, value);
  };

  Writer.prototype.unescapedValue = function unescapedValue(token, context) {
    var value = context.lookup(token[1]);
    if (value != null)
      return value;
  };

  Writer.prototype.escapedValue = function escapedValue(token, context) {
    var value = context.lookup(token[1]);
    if (value != null)
      return mustache.escape(value);
  };

  Writer.prototype.rawValue = function rawValue(token) {
    return token[1];
  };

  mustache.name = 'mustache.js';
  mustache.version = '2.3.0';
  mustache.tags = ['{{', '}}'];

  var defaultWriter = new Writer();

  mustache.clearCache = function clearCache() {
    return defaultWriter.clearCache();
  };

  mustache.parse = function parse(template, tags) {
    return defaultWriter.parse(template, tags);
  };

  mustache.render = function render(template, view, partials) {
    if (typeof template !== 'string') {
      throw new TypeError('Invalid template! Template should be a "string" ' +
        'but "' + typeStr(template) + '" was given as the first ' +
        'argument for mustache#render(template, view, partials)');
    }

    return defaultWriter.render(template, view, partials);
  };

  mustache.to_html = function to_html(template, view, partials, send) {

    var result = mustache.render(template, view, partials);

    if (isFunction(send)) {
      send(result);
    } else {
      return result;
    }
  };

  mustache.escape = escapeHtml;

  mustache.Scanner = Scanner;
  mustache.Context = Context;
  mustache.Writer = Writer;

  return mustache;
}));

//Get average card image color
function getAverageRGB(imgEl) {
  var blockSize = 5,
    defaultRGB = {
      r: 0,
      g: 0,
      b: 0
    },
    canvas = document.createElement('canvas'),
    context = canvas.getContext && canvas.getContext('2d'),
    data, width, height,
    i = -4,
    length,
    rgb = {
      r: 0,
      g: 0,
      b: 0
    },
    count = 0;
  if (!context) {
    return defaultRGB;
  }
  height = canvas.height = imgEl.naturalHeight || imgEl.offsetHeight || imgEl.height;
  width = canvas.width = imgEl.naturalWidth || imgEl.offsetWidth || imgEl.width;
  context.drawImage(imgEl, 0, 0);
  try {
    data = context.getImageData(0, 0, width, height);
  } catch (e) {
    alert('x');
    return defaultRGB;
  }
  length = data.data.length;
  while ((i += blockSize * 4) < length) {
    ++count;
    rgb.r += data.data[i];
    rgb.g += data.data[i + 1];
    rgb.b += data.data[i + 2];
  }
  rgb.r = ~~(rgb.r / count);
  rgb.g = ~~(rgb.g / count);
  rgb.b = ~~(rgb.b / count);
  return rgb;
}

//Set card header color by average => ColorThief
function cardTopCol() {
  $('.carde-header').each(function() {
    var imgID = $(this).parents('.carde').find('img').attr('id');
    var img = document.getElementById(imgID);
    var colorThief = new ColorThief();
    var colorThiefPatern = colorThief.getPalette(img)
    var kObj = colorThief.getColor(img);
    var array = colorThiefPatern[4];
    $(this).css('background', 'rgb(' + array[0] + ',' + array[1] + ',' + array[2] + ')');
  });
}

(function($) {
  $(document).ready(function() {
    //Header responsive
    var winWidth = $(window).width();
    var offset = $(".header-brand").offset().top;
    if (offset > 140) {
      $(".header-brand .header-nav").show();
    }
    $(document).scroll(function() {
      var scrollTop = $(document).scrollTop();
      if (scrollTop > 140 && winWidth > 824) {
        $(".header-brand .header-nav").fadeIn("slow");

      } else {
        $(".header-brand .header-nav").fadeOut("fast");
      }
    });
    // ============================================
    // TODO: Remove when JQ AJAX /!\ IMPORTANT /!\
    numColor();
    cardTopCol();
    // ============================================
  });

  // var jqxhr = $.getJSON('yolo.json', function(json) {
  //   var card = $('#cardList').html();
  //   Mustache.parse(card);
  //   var rendered = Mustache.render(card, json);
  //   $('#cardList').html(rendered).promise().done(function() {
  //     numColor();
  //     cardTopCol();
  //   });
  // }).fail(function() {
  //   console.log('JSON data loading failed');
  // });
var addCard = function (listtype, sort = "", sorttype = "") {
    var owlItem = ["<div class='item'><div class='carde'><div class='carde-header'><div class='carde-header-title'><h3>",
      "</h3></div><div class='carde-header-action'><i id='ionSuggest' class='ion-thumbsup'></i><i id='ionOther' class='ion-thumbsdown'></i></div></div><div class='carde-content'><img id='",
      "' class='img-circle' src='img/letters/a.png' alt=''><ul><li><h4>",
      "</h4></li><li><p>",
      "</p></li></ul></div><div class='carde-footer'><ul><li>Received <b>",
      "</b></li><li>Open Rate <b>",
      "%</b></li></ul></div></div></div>"
    ]
    args = "";
    if (sort != "") {
      args = "?sort=" + sort;
      if (sorttype != "") {
        args = args + "&sorttype=" + sorttype;
      }
    }
    $.ajax({
      url: "https://freeyourinbox.com/api/v1/api/v1/list/" + listtype + args,
      context: document.body
    }).done(function(data) {
      console.log(data);
      //Where?
      append = "we have analyzed " + data.stats.msgcount + " emails in " + data.stats.newscount + " newsletters<br>";
      if (data.stats.fullsync < 100) { append = append + 'fullsync in progress (' + data.stats.fullsync + '%) please wait...<br>'; }
      //==//

      $.each(data.results, function( key, value ) {
        $('#pre-filtering.allowed').trigger('add.owl.carousel', [owlItem[0]+value.fromname+owlItem[1]+value.id+owlItem[2]+value.from+owlItem[3]+value.subject+owlItem[4]+value.received+owlItem[5]+value.openrate+owlItem[6]])
        .trigger('refresh.owl.carousel');
  /*
   actions:
   0 = new
   1 = allow
   2 = digest
   3 = block
  */
        if (value.action != 1) {
          append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'allow\');">Allow</a>';
        }
        if (value.action != 2) {
          append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'digest\');">Digest</a>';
        }
        if (value.action != 3) {
          append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'filter\');">Block</a>';
        }
        if (value.action == 3) {
          append = append + ' <a href="javascript:fyi_action(' + value.id + ', \'unblock\');">Unblock</a>';
        }
        append = append + '</div><br>';
      });
      $("#result").html(append);
    });
  }



	//Header link onepage
  $('a').click(function() {
    if ($(this).parent().hasClass('nav-item')) {
      var active = '#' + $("[class*='nav-active']").attr('id');
      var id = $(this).parent().attr('id');
      var idNav = '#' + id;
      var idContent = '#p' + id.substr(1);
      var id = {
        nav: idNav,
        content: idContent
      };
      sectionSwitch(id, active);
    } else {
      return false;
    }
  });
  $('.more-nav .ion-android-more-vertical').click(function() {
    if ($('.xs-nav').is(":visible")) {
      $('.xs-item').hide();
      $('.xs-nav').animate({
        bottom: '100%'
      }, 200, function() {
        $('.xs-nav').hide();
      })
    } else {
      $('.xs-nav').show();
      $('.xs-item').show().parent().animate({
        bottom: '62%'
      }, 200)
    }
  });
	// Draw Nav Active
  function sectionSwitch(id, active) {
    var selectedId = $(id.content);
    var pActive = '#p' + active.substr(2);
    var pActive = $(pActive);
    var nActive = $(active);
    $("li").each(function(index) {
      if ($(this).hasClass('nav-active')) {
        $(this).removeClass('nav-active');;
      }
    });
    pActive.fadeOut('fast', function() {
      selectedId.fadeIn();
      $('.xs-nav').animate({
        bottom: '100%'
      }, 200, function() {
        $('.xs-nav').hide();
      })
      $("li").each(function(index) {
        if ($(this).is(id.nav)) {
          $(this).addClass("nav-active");
        }
      });
    });
  }
//Set color index for card info
  function numColor() {
    $('.card-bottom li b').each(function() {
      var val = $(this);
      if (val.text().includes('%')) {
        var percent = val.text().replace("%", '');
        if (percent < 33) {
          val.css('color', '#43A047')
        } else if (percent < 66) {
          val.css('color', '#FFB300')
        } else {
          val.css('color', '#DD2C00')
        }
      } else if (val.text() < 33) {
        val.css('color', '#43A047')
      } else if (val.text() < 66) {
        val.css('color', '#FFB300')
      } else {
        val.css('color', '#DD2C00')
      }
    });
  }
	//Show settings panel
  $('#setting').click(function() {
    var item = $(this);
    var offset = item.offset();
    if ($('.settings-pane').is(":visible")) {
      $('.settings-pane').fadeOut('fast')
    } else {
      console.log(offset.top);
      console.log(offset.left);
      $('.settings-pane').css({
        left: offset.left - 310 + 'px',
        top: offset.top + 'px',
        position: 'fixed'
      }).fadeIn('fast');
    }
  });
	//Show notification panel
  $('#notif').click(function() {
    var item = $(this);
    var offset = item.offset();
    if ($('.notification-pane').is(":visible")) {
      $('.notification-pane').fadeOut('fast')
    } else {
      console.log(offset.top);
      console.log(offset.left);
      $('.notification-pane').css({
        left: offset.left - 310 + 'px',
        top: offset.top + 'px',
        position: 'fixed'
      });
      $('.notification-pane').fadeIn('fast');
    }
  });

//First progres bar and loader OTH elements
  function progress() {
    var progr = document.getElementById('progress');
    var progress = 0;
    var id = setInterval(frame, 50);

    function frame() {
      if (progress > $('#loader').width()) {
        clearInterval(id);
        cardTopCol();
        $("#loader").fadeOut('fast', function() {
          $("#pNew").fadeIn('fast');
        });
      } else {
        progress += 25;
        //Set progress += 5;
        progr.style.width = progress + 'px';
      }
    }
  }
  progress();
	//Create JQuery Animator function
  $.fn.extend({
    revealAction: function(animationName) {
      var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
      this.addClass('animated ' + animationName).one(animationEnd, function() {
        $(this).removeClass('animated ' + animationName);
      });
      return this;
    }
  });
	//All card actions
  $('#blacklistAct').click(function() {
    $(this).parents('.card').fadeOut(function() {
      $(this).parent().remove();
    })
  });
  $('#whitelistAct').click(function() {
    $(this).parents('.card').fadeOut(function() {
      $(this).parent().remove();
    })
  });
  $('#digestAct').click(function() {
    $(this).parents('.card').fadeOut(function() {
      $(this).parent().remove();
    })
  });
  $('#suggestAct, #deleteAct').click(function() {
    $(this).parents('.card').fadeOut(function() {
      $(this).parent().remove();
    })
  });
  $('#modalActionActive').click(function() {
    $('.modal-action-overlay, .modal-action-content').fadeIn('fast');
  })
  $('.modal-action-overlay').click(function() {
    $('.modal-action-overlay, .modal-action-content').fadeOut('fast');
  })
  $('.carde-header-action .ion-thumbsdown').click(function() {
    var index = $(this).parents('.owl-item').index();
    if ($(this).parents('.allowed').length > 0) {
      $('.modal-card-content').attr('cardIndex', index).attr('cardType', 'allowed');
    } else {
      $('.modal-card-content').attr('cardIndex', index).attr('cardType', 'blocked');
    }
    $('.modal-card-overlay, .modal-card-content').fadeIn('fast');
  })
  $('.modal-card-overlay').click(function() {
    $('.modal-card-overlay, .modal-card-content').fadeOut('fast');
  })
	//Initialize Owl
  $('#pre-filtering.blocked').owlCarousel({
    loop: false,
    items: 3,
    margin: 0,
    autoplay: false,
    dots: false,
    smartSpeed: 450,
    nav: true,
    navText: [
      "<i class='ion-chevron-left'></i>",
      "<i class='ion-chevron-right'></i>"
    ],
    responsive: {
      0: {
        items: 1
      },
      870: {
        items: 2
      },
      1280: {
        items: 3
      }
    }
  });
  $('#pre-filtering.allowed').owlCarousel({
    loop: false,
    items: 3,
    margin: 0,
    autoplay: false,
    dots: false,
    smartSpeed: 450,
    nav: true,
    navText: [
      "<i class='ion-chevron-left'></i>",
      "<i class='ion-chevron-right'></i>"
    ],
    responsive: {
      0: {
        items: 1
      },
      870: {
        items: 2
      },
      1280: {
        items: 3
      }
    }

  });
	//Owl Prev/Next button
  $('.owl-next i').click(function() {
    $(this).parents('.owl-nav').children('.owl-prev').animate({
      'opacity': 1
    }, 300);
  });
	//Remove card on user action
  $('.ion-thumbsup').click(function() {
    var index = $(this).parents('.owl-item').index()
    if ($(this).parents('.allowed').length > 0) {
      $(".allowed").trigger('remove.owl.carousel', [index]).trigger('refresh.owl.carousel');
      if ($(".allowed").find('.owl-stage').children().length <= 0) {
        $(".allowed").find('.owl-stage').html("<h3 class='noMoreContent animated fadeIn'>Congratulations, all your mails have been processed</h3>");
      }
    } else {
      $(".blocked").trigger('remove.owl.carousel', [index]).trigger('refresh.owl.carousel');
      if ($(".blocked").find('.owl-stage').children().length <= 0) {
        $(".blocked").find('.owl-stage').html("<h3 class='noMoreContent animated fadeIn'>Congratulations, all your mails have been processed</h3>");
      }
    }

  });
	//Where the card should go
  function location(location) {
    console.log(location);
  }
	//Action after active modal
  $('#modalGoWhite').click(function() {
    // FIXME: REMOVE 2 CARDS
    // FIXME: TEXT NOT APPEND
    var index = $(this).parents('.modal-card-content').attr('cardIndex');
    var type = $(this).parents('.modal-card-content').attr('cardType');
    $('.modal-card-overlay, .modal-card-content').fadeOut('fast', function() {
      $("." + type).trigger('remove.owl.carousel', [index]).trigger('refresh.owl.carousel');
    });
    if ($("." + type).find('.owl-stage').children().length <= 0) {
      $("." + type).find('.owl-stage').html("<h3 class='noMoreContent animated fadeIn'>Congratulations, all your mails have been processed</h3>");
    }
    whereToGo('whitelist');
  });
  $('#modalGoBlack').click(function() {
    // FIXME: REMOVE 2 CARDS
    // FIXME: TEXT NOT APPEND
    var index = $(this).parents('.modal-card-content').attr('cardIndex');
    var type = $(this).parents('.modal-card-content').attr('cardType');
    $('.modal-card-overlay, .modal-card-content').fadeOut('fast', function() {
      $("." + type).trigger('remove.owl.carousel', [index]).trigger('refresh.owl.carousel');
    });
    if ($("." + type).find('.owl-stage').children().length <= 0) {
      $("." + type).find('.owl-stage').html("<h3 class='noMoreContent animated fadeIn'>Congratulations, all your mails have been processed</h3>");
    }
    whereToGo('blacklist');
  });
  $('#modalGoDigest').click(function() {
    // FIXME: REMOVE 2 CARDS
    // FIXME: TEXT NOT APPEND
    var index = $(this).parents('.modal-card-content').attr('cardIndex');
    var type = $(this).parents('.modal-card-content').attr('cardType');
    $('.modal-card-overlay, .modal-card-content').fadeOut('fast', function() {
      $("." + type).trigger('remove.owl.carousel', [index]).trigger('refresh.owl.carousel');
    });
    if ($("." + type).find('.owl-stage').children().length <= 0) {
      $("." + type).find('.owl-stage').html("<h3 class='noMoreContent animated fadeIn'>Congratulations, all your mails have been processed</h3>");
    }
    whereToGo('digest');
  });
	//Search bar
  $('#seek').keypress(function(e) {
    if (e.which == 13) {
      alertDisplay('Search input currently unavailable.')
    }
  });
  $('.webflow-style-input').click(function() {
    alertDisplay('Search input currently unavailable.')
  });
	//Header Alert
  function alertDisplay(text) {
    $('.alert-display-text').text(text);
    $(".alert-display").show().animate({
      'top': '4.4em'
    }, 300, function() {
      setTimeout(function() {
        $(".alert-display").animate({
          'top': '0em'
        }, 300, function() {
          $(this).hide();
          $('.alert-display-text').text('Alert!')
        })
      }, 3000);
    })
  }
	// If first connection
	if (true) {

	}
	//Cookie: Setter
  function setCookie(name, parameters, time){
    $.cookie(name , parameters, {
      expires: time
    });
  }
	//Cookie: Getter
	function getCookie (cookie){
		var chocolateCookie = $.cookie(cookie);
		return chocolateCookie;
	}
  //Get cookie by var

	//var myCookie = getCookie('cookie');
})(jQuery);
