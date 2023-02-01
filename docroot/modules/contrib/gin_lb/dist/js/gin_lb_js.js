/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "../";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./js/gin_lb.js":
/*!**********************!*\
  !*** ./js/gin_lb.js ***!
  \**********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _gin_lb_lb__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./gin_lb_lb */ \"./js/gin_lb_lb.js\");\n/* harmony import */ var _gin_lb_lb__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_gin_lb_lb__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var _gin_lb_offcanvas__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./gin_lb_offcanvas */ \"./js/gin_lb_offcanvas.js\");\n/* harmony import */ var _gin_lb_offcanvas__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_gin_lb_offcanvas__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _gin_lb_toolbar__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./gin_lb_toolbar */ \"./js/gin_lb_toolbar.js\");\n/* harmony import */ var _gin_lb_toolbar__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_gin_lb_toolbar__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _gin_lb_preview_regions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./gin_lb_preview_regions */ \"./js/gin_lb_preview_regions.js\");\n/* harmony import */ var _gin_lb_preview_regions__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_gin_lb_preview_regions__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var _gin_lb_toastify__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./gin_lb_toastify */ \"./js/gin_lb_toastify.js\");\n/* harmony import */ var _gin_lb_toastify__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_gin_lb_toastify__WEBPACK_IMPORTED_MODULE_4__);\n\n\n\n\n//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGIuanM/Yzk4NCJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQXFCO0FBQ087QUFDRjtBQUNRIiwiZmlsZSI6Ii4vanMvZ2luX2xiLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0ICcuL2dpbl9sYl9sYic7XG5pbXBvcnQgJy4vZ2luX2xiX29mZmNhbnZhcyc7XG5pbXBvcnQgJy4vZ2luX2xiX3Rvb2xiYXInO1xuaW1wb3J0ICcuL2dpbl9sYl9wcmV2aWV3X3JlZ2lvbnMnO1xuaW1wb3J0ICcuL2dpbl9sYl90b2FzdGlmeSc7XG5cbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./js/gin_lb.js\n");

/***/ }),

/***/ "./js/gin_lb_lb.js":
/*!*************************!*\
  !*** ./js/gin_lb_lb.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */\n\n\n\n(($, Drupal, drupalSettings) => {\n  Drupal.behaviors.gin_lb_lb = {\n    attach: context => {\n      once('gin-lb-lb', '.layout-builder-block', context).forEach(elm => {\n        var $div = $(elm);\n        const activeClass = 'gin-lb--disable-section-focus';\n        const observer = new MutationObserver(function (mutations) {\n          mutations.forEach(function (mutation) {\n            if (mutation.attributeName === \"class\") {\n              if ($(mutation.target).hasClass('focus')) {\n                $(mutation.target).parents('.layout-builder__section').addClass(activeClass);\n              } else {\n                $(mutation.target).parents('.layout-builder__section').removeClass(activeClass);\n              }\n            }\n          });\n        });\n        observer.observe($div[0], {\n          attributes: true\n        });\n      });\n    }\n  };\n})(jQuery, Drupal, drupalSettings);//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGJfbGIuanM/NjQ2ZCJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ2E7QUFDYjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiIuL2pzL2dpbl9sYl9sYi5qcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qIGVzbGludC1kaXNhYmxlIG5vLWJpdHdpc2UsIG5vLW5lc3RlZC10ZXJuYXJ5LCBuby1tdXRhYmxlLWV4cG9ydHMsIGNvbW1hLWRhbmdsZSwgc3RyaWN0ICovXG5cbid1c2Ugc3RyaWN0JztcblxuKCgkLCBEcnVwYWwsIGRydXBhbFNldHRpbmdzKSA9PiB7XG5cbiAgRHJ1cGFsLmJlaGF2aW9ycy5naW5fbGJfbGIgPSB7XG4gICAgYXR0YWNoOiAoY29udGV4dCkgPT4ge1xuICAgICAgb25jZSgnZ2luLWxiLWxiJywgJy5sYXlvdXQtYnVpbGRlci1ibG9jaycsIGNvbnRleHQpLmZvckVhY2goKGVsbSk9PntcbiAgICAgICAgdmFyICRkaXYgPSAkKGVsbSk7XG4gICAgICAgIGNvbnN0IGFjdGl2ZUNsYXNzID0gJ2dpbi1sYi0tZGlzYWJsZS1zZWN0aW9uLWZvY3VzJztcbiAgICAgICAgY29uc3Qgb2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcihmdW5jdGlvbihtdXRhdGlvbnMpIHtcbiAgICAgICAgICBtdXRhdGlvbnMuZm9yRWFjaChmdW5jdGlvbihtdXRhdGlvbikge1xuICAgICAgICAgICAgaWYgKG11dGF0aW9uLmF0dHJpYnV0ZU5hbWUgPT09IFwiY2xhc3NcIikge1xuICAgICAgICAgICAgICBpZiAoJChtdXRhdGlvbi50YXJnZXQpLmhhc0NsYXNzKCdmb2N1cycpKSB7XG4gICAgICAgICAgICAgICAgJChtdXRhdGlvbi50YXJnZXQpLnBhcmVudHMoJy5sYXlvdXQtYnVpbGRlcl9fc2VjdGlvbicpLmFkZENsYXNzKGFjdGl2ZUNsYXNzKTtcbiAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAkKG11dGF0aW9uLnRhcmdldCkucGFyZW50cygnLmxheW91dC1idWlsZGVyX19zZWN0aW9uJykucmVtb3ZlQ2xhc3MoYWN0aXZlQ2xhc3MpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuICAgICAgICBvYnNlcnZlci5vYnNlcnZlKCRkaXZbMF0sIHtcbiAgICAgICAgICBhdHRyaWJ1dGVzOiB0cnVlXG4gICAgICAgIH0pO1xuICAgICAgfSlcbiAgICB9XG4gIH07XG5cbn0pKGpRdWVyeSwgRHJ1cGFsLCBkcnVwYWxTZXR0aW5ncyk7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./js/gin_lb_lb.js\n");

/***/ }),

/***/ "./js/gin_lb_offcanvas.js":
/*!********************************!*\
  !*** ./js/gin_lb_offcanvas.js ***!
  \********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */\n\n\n\n(($, Drupal, drupalSettings) => {\n  Drupal.behaviors.offCanvas = {\n    width: localStorage.getItem('gin_lb_offcanvas_width') != null ? localStorage.getItem('gin_lb_offcanvas_width') : (drupalSettings.gin_lb || {}).offcanvas_width != null ? drupalSettings.gin_lb.offcanvas_width : 450,\n    links: [],\n    attach: () => {\n      function setOptions(elm) {\n        const dataDialog = $(elm).data('dialog-options');\n        const dialogOptions = dataDialog != null ? dataDialog : {};\n        dialogOptions.width = Drupal.behaviors.offCanvas.width;\n        $(elm).attr('data-dialog-options', JSON.stringify(dialogOptions));\n        Drupal.ajax.instances.forEach(item => {\n          if (item != null && item.dialogRenderer === 'off_canvas') {\n            if (item.options.data.dialogOptions == null) {\n              item.options.data.dialogOptions = {};\n            }\n            if (dialogOptions.width) {\n              item.options.data.dialogOptions.width = dialogOptions.width;\n            }\n          }\n        });\n      }\n      $('body').once('gin-canvas-event').each(() => {\n        $('body').on('dialogresizestop', (event, ui) => {\n          const sidebar = $('.ui-dialog-off-canvas');\n          Drupal.behaviors.offCanvas.width = sidebar.width();\n          localStorage.setItem('gin_lb_offcanvas_width', Drupal.behaviors.offCanvas.width);\n          Drupal.behaviors.offCanvas.links.forEach(elm => {\n            setOptions(elm);\n          });\n        });\n      });\n      window.setTimeout(() => {\n        $('a[data-dialog-renderer]').once('glb-offcanvas-width').each((item, elm) => {\n          Drupal.behaviors.offCanvas.links.push(elm);\n          setOptions(elm);\n        });\n      }, 300);\n    }\n  };\n})(jQuery, Drupal, drupalSettings);//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGJfb2ZmY2FudmFzLmpzPzQ3MWYiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNhO0FBQ2I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiIuL2pzL2dpbl9sYl9vZmZjYW52YXMuanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiBlc2xpbnQtZGlzYWJsZSBuby1iaXR3aXNlLCBuby1uZXN0ZWQtdGVybmFyeSwgbm8tbXV0YWJsZS1leHBvcnRzLCBjb21tYS1kYW5nbGUsIHN0cmljdCAqL1xuXG4ndXNlIHN0cmljdCc7XG5cbigoJCwgRHJ1cGFsLCBkcnVwYWxTZXR0aW5ncykgPT4ge1xuXG4gIERydXBhbC5iZWhhdmlvcnMub2ZmQ2FudmFzID0ge1xuICAgIHdpZHRoOiBsb2NhbFN0b3JhZ2UuZ2V0SXRlbSgnZ2luX2xiX29mZmNhbnZhc193aWR0aCcpICE9IG51bGwgPyBsb2NhbFN0b3JhZ2UuZ2V0SXRlbSgnZ2luX2xiX29mZmNhbnZhc193aWR0aCcpIDogKChkcnVwYWxTZXR0aW5ncy5naW5fbGIgfHwge30pLm9mZmNhbnZhc193aWR0aCAhPSBudWxsID8gZHJ1cGFsU2V0dGluZ3MuZ2luX2xiLm9mZmNhbnZhc193aWR0aCA6IDQ1MCksXG4gICAgbGlua3M6IFtdLFxuICAgIGF0dGFjaDogKCkgPT4ge1xuICAgICAgZnVuY3Rpb24gc2V0T3B0aW9ucyhlbG0pIHtcbiAgICAgICAgY29uc3QgZGF0YURpYWxvZyA9ICQoZWxtKS5kYXRhKCdkaWFsb2ctb3B0aW9ucycpO1xuICAgICAgICBjb25zdCBkaWFsb2dPcHRpb25zID0gZGF0YURpYWxvZyAhPSBudWxsID8gZGF0YURpYWxvZyA6IHt9O1xuICAgICAgICBkaWFsb2dPcHRpb25zLndpZHRoID0gRHJ1cGFsLmJlaGF2aW9ycy5vZmZDYW52YXMud2lkdGg7XG4gICAgICAgICQoZWxtKS5hdHRyKCdkYXRhLWRpYWxvZy1vcHRpb25zJywgSlNPTi5zdHJpbmdpZnkoZGlhbG9nT3B0aW9ucykpO1xuICAgICAgICBEcnVwYWwuYWpheC5pbnN0YW5jZXMuZm9yRWFjaCgoaXRlbSk9PntcblxuICAgICAgICAgIGlmIChpdGVtICE9IG51bGwgJiYgaXRlbS5kaWFsb2dSZW5kZXJlciA9PT0gJ29mZl9jYW52YXMnICkge1xuICAgICAgICAgICAgaWYgKGl0ZW0ub3B0aW9ucy5kYXRhLmRpYWxvZ09wdGlvbnMgPT0gbnVsbCkge1xuICAgICAgICAgICAgICBpdGVtLm9wdGlvbnMuZGF0YS5kaWFsb2dPcHRpb25zID0ge307XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoZGlhbG9nT3B0aW9ucy53aWR0aCkge1xuICAgICAgICAgICAgICBpdGVtLm9wdGlvbnMuZGF0YS5kaWFsb2dPcHRpb25zLndpZHRoID0gZGlhbG9nT3B0aW9ucy53aWR0aFxuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgIH1cbiAgICAgICQoJ2JvZHknKS5vbmNlKCdnaW4tY2FudmFzLWV2ZW50JykuZWFjaCgoKT0+e1xuICAgICAgICAkKCdib2R5Jykub24oICdkaWFsb2dyZXNpemVzdG9wJywgKCBldmVudCwgdWkgKSA9PiB7XG4gICAgICAgICAgY29uc3Qgc2lkZWJhciA9ICQoJy51aS1kaWFsb2ctb2ZmLWNhbnZhcycpO1xuICAgICAgICAgIERydXBhbC5iZWhhdmlvcnMub2ZmQ2FudmFzLndpZHRoID0gc2lkZWJhci53aWR0aCgpO1xuICAgICAgICAgIGxvY2FsU3RvcmFnZS5zZXRJdGVtKCdnaW5fbGJfb2ZmY2FudmFzX3dpZHRoJywgRHJ1cGFsLmJlaGF2aW9ycy5vZmZDYW52YXMud2lkdGgpO1xuICAgICAgICAgIERydXBhbC5iZWhhdmlvcnMub2ZmQ2FudmFzLmxpbmtzLmZvckVhY2goKGVsbSk9PntcbiAgICAgICAgICAgIHNldE9wdGlvbnMoZWxtKTtcbiAgICAgICAgICB9KVxuICAgICAgICB9KTtcbiAgICAgIH0pXG4gICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICQoJ2FbZGF0YS1kaWFsb2ctcmVuZGVyZXJdJykub25jZSgnZ2xiLW9mZmNhbnZhcy13aWR0aCcpLmVhY2goKGl0ZW0sIGVsbSk9PiB7XG4gICAgICAgICAgRHJ1cGFsLmJlaGF2aW9ycy5vZmZDYW52YXMubGlua3MucHVzaChlbG0pO1xuICAgICAgICAgIHNldE9wdGlvbnMoZWxtKTtcbiAgICAgICAgfSk7XG4gICAgICB9LCAzMDApXG4gICAgfVxuICB9O1xuXG59KShqUXVlcnksIERydXBhbCwgZHJ1cGFsU2V0dGluZ3MpO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./js/gin_lb_offcanvas.js\n");

/***/ }),

/***/ "./js/gin_lb_preview_regions.js":
/*!**************************************!*\
  !*** ./js/gin_lb_preview_regions.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */\n\n\n\n(($, Drupal, drupalSettings) => {\n  Drupal.behaviors.glb_preview_regions = {\n    attach: context => {\n      once('glb-preview-region', 'body').forEach(() => {\n        const toolbarPreviewRegion = document.getElementById('glb-toolbar-preview-regions');\n        const toolbarPreviewContent = document.getElementById('glb-toolbar-preview-content');\n        const formPreviewContent = document.getElementById('layout-builder-content-preview');\n        const body = document.getElementsByTagName('body')[0];\n        toolbarPreviewContent.checked = formPreviewContent.checked;\n        ;\n        toolbarPreviewRegion.checked = body.classList.contains('glb-preview-regions--enable');\n        toolbarPreviewRegion.addEventListener('change', () => {\n          if (toolbarPreviewRegion.checked) {\n            document.querySelector('.layout__region-info').parentNode.classList.add('layout-builder__region');\n            document.querySelector('body').classList.add('glb-preview-regions--enable');\n          } else {\n            body.classList.remove('glb-preview-regions--enable');\n          }\n        });\n        toolbarPreviewContent.addEventListener('change', () => {\n          if (formPreviewContent.checked !== toolbarPreviewContent.checked) {\n            formPreviewContent.click();\n          }\n        });\n      });\n    }\n  };\n})(jQuery, Drupal, drupalSettings);//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGJfcHJldmlld19yZWdpb25zLmpzP2IwNjUiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7QUFDQTtBQUNhO0FBQ2I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiIuL2pzL2dpbl9sYl9wcmV2aWV3X3JlZ2lvbnMuanMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKiBlc2xpbnQtZGlzYWJsZSBuby1iaXR3aXNlLCBuby1uZXN0ZWQtdGVybmFyeSwgbm8tbXV0YWJsZS1leHBvcnRzLCBjb21tYS1kYW5nbGUsIHN0cmljdCAqL1xuXG4ndXNlIHN0cmljdCc7XG5cbigoJCwgRHJ1cGFsLCBkcnVwYWxTZXR0aW5ncykgPT4ge1xuXG4gIERydXBhbC5iZWhhdmlvcnMuZ2xiX3ByZXZpZXdfcmVnaW9ucyA9IHtcbiAgICBhdHRhY2g6IChjb250ZXh0KSA9PiB7XG4gICAgICBvbmNlKCdnbGItcHJldmlldy1yZWdpb24nLCAnYm9keScpLmZvckVhY2goKCk9PntcbiAgICAgICAgY29uc3QgdG9vbGJhclByZXZpZXdSZWdpb24gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZ2xiLXRvb2xiYXItcHJldmlldy1yZWdpb25zJyk7XG4gICAgICAgIGNvbnN0IHRvb2xiYXJQcmV2aWV3Q29udGVudCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdnbGItdG9vbGJhci1wcmV2aWV3LWNvbnRlbnQnKTtcbiAgICAgICAgY29uc3QgZm9ybVByZXZpZXdDb250ZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2xheW91dC1idWlsZGVyLWNvbnRlbnQtcHJldmlldycpO1xuICAgICAgICBjb25zdCBib2R5ID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeVRhZ05hbWUoJ2JvZHknKVswXTtcbiAgICAgICAgdG9vbGJhclByZXZpZXdDb250ZW50LmNoZWNrZWQgPSBmb3JtUHJldmlld0NvbnRlbnQuY2hlY2tlZDs7XG4gICAgICAgIHRvb2xiYXJQcmV2aWV3UmVnaW9uLmNoZWNrZWQgPSBib2R5LmNsYXNzTGlzdC5jb250YWlucygnZ2xiLXByZXZpZXctcmVnaW9ucy0tZW5hYmxlJyk7XG5cbiAgICAgICAgdG9vbGJhclByZXZpZXdSZWdpb24uYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywoKT0+e1xuICAgICAgICAgIGlmKHRvb2xiYXJQcmV2aWV3UmVnaW9uLmNoZWNrZWQpe1xuICAgICAgICAgICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmxheW91dF9fcmVnaW9uLWluZm8nKS5wYXJlbnROb2RlLmNsYXNzTGlzdC5hZGQoJ2xheW91dC1idWlsZGVyX19yZWdpb24nKTtcbiAgICAgICAgICAgIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ2JvZHknKS5jbGFzc0xpc3QuYWRkKCdnbGItcHJldmlldy1yZWdpb25zLS1lbmFibGUnKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgYm9keS5jbGFzc0xpc3QucmVtb3ZlKCdnbGItcHJldmlldy1yZWdpb25zLS1lbmFibGUnKVxuICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgICAgdG9vbGJhclByZXZpZXdDb250ZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsKCk9PntcbiAgICAgICAgICBpZiAoZm9ybVByZXZpZXdDb250ZW50LmNoZWNrZWQgIT09IHRvb2xiYXJQcmV2aWV3Q29udGVudC5jaGVja2VkKSB7XG4gICAgICAgICAgICBmb3JtUHJldmlld0NvbnRlbnQuY2xpY2soKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pXG4gICAgICB9KTtcblxuICAgIH1cbiAgfTtcblxufSkoalF1ZXJ5LCBEcnVwYWwsIGRydXBhbFNldHRpbmdzKTtcbiJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./js/gin_lb_preview_regions.js\n");

/***/ }),

/***/ "./js/gin_lb_toastify.js":
/*!*******************************!*\
  !*** ./js/gin_lb_toastify.js ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */\n\n\n\n(($, Drupal, drupalSettings) => {\n  Drupal.behaviors.ginLbToastify = {\n    attach: context => {\n      let offset = $('.ui-dialog-off-canvas').length ? $('.ui-dialog-off-canvas').width() : 0;\n      once('glb-messages-warning', '.glb-messages--warning', context).forEach(item => {\n        if ($(item).hasClass('toastify')) {\n          return;\n        }\n        Toastify({\n          text: $(item).html(),\n          escapeMarkup: false,\n          close: true,\n          gravity: \"bottom\",\n          duration: 6000,\n          position: \"right\",\n          offset: {\n            x: 0\n          },\n          className: \"glb-messages glb-messages--warning\",\n          style: {\n            background: \"var(--colorGinWarningBackground)\"\n          }\n        }).showToast();\n        $(item).hide();\n      });\n      once('glb-messages-error', '.glb-messages--error', context).forEach(item => {\n        if ($(item).hasClass('toastify')) {\n          return;\n        }\n        Toastify({\n          text: $(item).html(),\n          escapeMarkup: false,\n          gravity: \"bottom\",\n          duration: 6000,\n          position: \"right\",\n          close: true,\n          offset: {\n            x: offset\n          },\n          className: \"glb-messages glb-messages--error\",\n          style: {\n            background: \"var(--colorGinErrorBackground)\"\n          }\n        }).showToast();\n        $(item).hide();\n      });\n      once('glb-messages-status', '.glb-messages--status', context).forEach(item => {\n        if ($(item).hasClass('toastify')) {\n          return;\n        }\n        if ($(item).parents('.glb-sidebar__content').length >= 1) {\n          return;\n        }\n        Toastify({\n          text: $(item).html(),\n          escapeMarkup: false,\n          close: true,\n          gravity: \"bottom\",\n          duration: 6000,\n          position: \"right\",\n          offset: {\n            x: offset\n          },\n          className: \"glb-messages glb-messages--status\",\n          style: {\n            background: \"var(--colorGinStatusBackground)\"\n          }\n        }).showToast();\n        $(item).hide();\n      });\n    }\n  };\n})(jQuery, Drupal, drupalSettings);//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGJfdG9hc3RpZnkuanM/MmEwOSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ2E7QUFDYjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBIiwiZmlsZSI6Ii4vanMvZ2luX2xiX3RvYXN0aWZ5LmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyogZXNsaW50LWRpc2FibGUgbm8tYml0d2lzZSwgbm8tbmVzdGVkLXRlcm5hcnksIG5vLW11dGFibGUtZXhwb3J0cywgY29tbWEtZGFuZ2xlLCBzdHJpY3QgKi9cblxuJ3VzZSBzdHJpY3QnO1xuXG4oKCQsIERydXBhbCwgZHJ1cGFsU2V0dGluZ3MpID0+IHtcblxuICBEcnVwYWwuYmVoYXZpb3JzLmdpbkxiVG9hc3RpZnkgPSB7XG4gICAgYXR0YWNoOiAoY29udGV4dCkgPT4ge1xuICAgICAgbGV0IG9mZnNldCA9ICQoICcudWktZGlhbG9nLW9mZi1jYW52YXMnICkubGVuZ3RoID8gJCggJy51aS1kaWFsb2ctb2ZmLWNhbnZhcycpLndpZHRoKCkgOiAwO1xuXG4gICAgICBvbmNlKCdnbGItbWVzc2FnZXMtd2FybmluZycsICcuZ2xiLW1lc3NhZ2VzLS13YXJuaW5nJywgY29udGV4dCkuZm9yRWFjaCgoaXRlbSk9PntcbiAgICAgICAgaWYgKCQoaXRlbSkuaGFzQ2xhc3MoJ3RvYXN0aWZ5JykpIHtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgVG9hc3RpZnkoe1xuICAgICAgICAgIHRleHQ6ICQoaXRlbSkuaHRtbCgpLFxuICAgICAgICAgIGVzY2FwZU1hcmt1cDogZmFsc2UsXG4gICAgICAgICAgY2xvc2U6IHRydWUsXG4gICAgICAgICAgZ3Jhdml0eTogXCJib3R0b21cIixcbiAgICAgICAgICBkdXJhdGlvbjogNjAwMCxcbiAgICAgICAgICBwb3NpdGlvbjogXCJyaWdodFwiLFxuICAgICAgICAgIG9mZnNldDoge1xuICAgICAgICAgICAgeDogMCxcbiAgICAgICAgICB9LFxuICAgICAgICAgIGNsYXNzTmFtZTpcImdsYi1tZXNzYWdlcyBnbGItbWVzc2FnZXMtLXdhcm5pbmdcIixcbiAgICAgICAgICBzdHlsZToge1xuICAgICAgICAgICAgYmFja2dyb3VuZDogXCJ2YXIoLS1jb2xvckdpbldhcm5pbmdCYWNrZ3JvdW5kKVwiLFxuICAgICAgICAgIH0sXG4gICAgICAgIH0pLnNob3dUb2FzdCgpO1xuICAgICAgICAkKGl0ZW0pLmhpZGUoKTtcbiAgICAgIH0pXG4gICAgICBvbmNlKCdnbGItbWVzc2FnZXMtZXJyb3InLCAnLmdsYi1tZXNzYWdlcy0tZXJyb3InLCBjb250ZXh0KS5mb3JFYWNoKChpdGVtKT0+e1xuICAgICAgICBpZiAoJChpdGVtKS5oYXNDbGFzcygndG9hc3RpZnknKSkge1xuICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBUb2FzdGlmeSh7XG4gICAgICAgICAgdGV4dDogJChpdGVtKS5odG1sKCksXG4gICAgICAgICAgZXNjYXBlTWFya3VwOiBmYWxzZSxcbiAgICAgICAgICBncmF2aXR5OiBcImJvdHRvbVwiLFxuICAgICAgICAgIGR1cmF0aW9uOiA2MDAwLFxuICAgICAgICAgIHBvc2l0aW9uOiBcInJpZ2h0XCIsXG4gICAgICAgICAgY2xvc2U6IHRydWUsXG4gICAgICAgICAgb2Zmc2V0OiB7XG4gICAgICAgICAgICB4OiBvZmZzZXQsXG4gICAgICAgICAgfSxcbiAgICAgICAgICBjbGFzc05hbWU6XCJnbGItbWVzc2FnZXMgZ2xiLW1lc3NhZ2VzLS1lcnJvclwiLFxuICAgICAgICAgIHN0eWxlOiB7XG4gICAgICAgICAgICBiYWNrZ3JvdW5kOiBcInZhcigtLWNvbG9yR2luRXJyb3JCYWNrZ3JvdW5kKVwiLFxuICAgICAgICAgIH0sXG4gICAgICAgIH0pLnNob3dUb2FzdCgpO1xuICAgICAgICAkKGl0ZW0pLmhpZGUoKTtcbiAgICAgIH0pXG4gICAgICBvbmNlKCdnbGItbWVzc2FnZXMtc3RhdHVzJywgJy5nbGItbWVzc2FnZXMtLXN0YXR1cycsIGNvbnRleHQpLmZvckVhY2goKGl0ZW0pPT57XG4gICAgICAgIGlmICgkKGl0ZW0pLmhhc0NsYXNzKCd0b2FzdGlmeScpKSB7XG4gICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGlmICgkKGl0ZW0pLnBhcmVudHMoJy5nbGItc2lkZWJhcl9fY29udGVudCcpLmxlbmd0aCA+PSAxKSB7XG4gICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIFRvYXN0aWZ5KHtcbiAgICAgICAgICB0ZXh0OiAkKGl0ZW0pLmh0bWwoKSxcbiAgICAgICAgICBlc2NhcGVNYXJrdXA6IGZhbHNlLFxuICAgICAgICAgIGNsb3NlOiB0cnVlLFxuICAgICAgICAgIGdyYXZpdHk6IFwiYm90dG9tXCIsXG4gICAgICAgICAgZHVyYXRpb246IDYwMDAsXG4gICAgICAgICAgcG9zaXRpb246IFwicmlnaHRcIixcbiAgICAgICAgICBvZmZzZXQ6IHtcbiAgICAgICAgICAgIHg6IG9mZnNldCxcbiAgICAgICAgICB9LFxuICAgICAgICAgIGNsYXNzTmFtZTpcImdsYi1tZXNzYWdlcyBnbGItbWVzc2FnZXMtLXN0YXR1c1wiLFxuICAgICAgICAgIHN0eWxlOiB7XG4gICAgICAgICAgICBiYWNrZ3JvdW5kOiBcInZhcigtLWNvbG9yR2luU3RhdHVzQmFja2dyb3VuZClcIixcbiAgICAgICAgICB9LFxuICAgICAgICB9KS5zaG93VG9hc3QoKTtcbiAgICAgICAgJChpdGVtKS5oaWRlKCk7XG4gICAgICB9KTtcbiAgICB9XG4gIH07XG5cbn0pKGpRdWVyeSwgRHJ1cGFsLCBkcnVwYWxTZXR0aW5ncyk7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./js/gin_lb_toastify.js\n");

/***/ }),

/***/ "./js/gin_lb_toolbar.js":
/*!******************************!*\
  !*** ./js/gin_lb_toolbar.js ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
eval("/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */\n\n\n\n(($, Drupal, drupalSettings) => {\n  Drupal.behaviors.ginLbToolbar = {\n    attach: context => {\n      once('glb-primary-save', '.glb-primary-save ').forEach(item => {\n        item.addEventListener('click', function (event) {\n          document.querySelector('#gin_sidebar .form-actions .glb-button--primary').click();\n        });\n      });\n    }\n  };\n})(jQuery, Drupal, drupalSettings);//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9qcy9naW5fbGJfdG9vbGJhci5qcz9lNGExIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBO0FBQ0E7QUFDYTtBQUNiO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiIuL2pzL2dpbl9sYl90b29sYmFyLmpzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyogZXNsaW50LWRpc2FibGUgbm8tYml0d2lzZSwgbm8tbmVzdGVkLXRlcm5hcnksIG5vLW11dGFibGUtZXhwb3J0cywgY29tbWEtZGFuZ2xlLCBzdHJpY3QgKi9cblxuJ3VzZSBzdHJpY3QnO1xuXG4oKCQsIERydXBhbCwgZHJ1cGFsU2V0dGluZ3MpID0+IHtcblxuICBEcnVwYWwuYmVoYXZpb3JzLmdpbkxiVG9vbGJhciA9IHtcbiAgICBhdHRhY2g6IChjb250ZXh0KSA9PiB7XG4gICAgICBvbmNlKCdnbGItcHJpbWFyeS1zYXZlJywgJy5nbGItcHJpbWFyeS1zYXZlICcpLmZvckVhY2goKGl0ZW0pPT57XG4gICAgICAgIGl0ZW0uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgICBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjZ2luX3NpZGViYXIgLmZvcm0tYWN0aW9ucyAuZ2xiLWJ1dHRvbi0tcHJpbWFyeScpLmNsaWNrKCk7XG4gICAgICAgIH0pO1xuICAgICAgfSlcbiAgICB9XG4gIH1cbn0pKGpRdWVyeSwgRHJ1cGFsLCBkcnVwYWxTZXR0aW5ncyk7XG4iXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./js/gin_lb_toolbar.js\n");

/***/ }),

/***/ 1:
/*!****************************!*\
  !*** multi ./js/gin_lb.js ***!
  \****************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./js/gin_lb.js */"./js/gin_lb.js");


/***/ })

/******/ });
//# sourceMappingURL=gin_lb_js.js.map