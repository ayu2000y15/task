import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// jQueryの読み込みとグローバル登録
import jQuery from "jquery";
window.$ = window.jQuery = jQuery;
