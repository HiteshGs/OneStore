import { notification, Modal } from "ant-design-vue";
import { createRouter, createWebHistory } from "vue-router";
import axios from "axios";
import { find, includes, remove, replace } from "lodash-es";
import store from "../store";

import AuthRoutes from "./auth";
import DashboardRoutes from "./dashboard";
import ProductRoutes from "./products";
import StockRoutes from "./stocks";
import ExpensesRoutes from "./expenses";
import UserRoutes from "./users";
import SettingRoutes from "./settings";
import ReportsRoutes from "./reports";
import SetupAppRoutes from "./setupApp";
import StaffRoutes from "./hrm/staff";
import LeaveRoutes from "./hrm/leaves";
import HolidayRoutes from "./hrm/holiday";
import AttendanceRoutes from "./hrm/attendance";
import PayrollRoutes from "./hrm/payroll";
import AppreciationRoutes from "./hrm/appreciations";
import HrmDashboardRoutes from "./hrm/hrmDashboard";
import HrmSettingsRoutes from "./hrm/hrmSettings";
import { checkUserPermission } from "../../common/scripts/functions";

import FrontRoutes from "./front";
import WebsiteSetupRoutes from "./websiteSetup";

const appType = window.config.app_type;
const allActiveModules = window.config.modules;

const isAdminCompanySetupCorrect = () => {
    var appSetting = store.state.auth.appSetting;

    if (appSetting.x_currency_id == null || appSetting.x_warehouse_id == null) {
        return false;
    }

    return true;
};

const isSuperAdminCompanySetupCorrect = () => {
    var appSetting = store.state.auth.appSetting;

    if (
        appSetting.x_currency_id == null ||
        appSetting.white_label_completed == false
    ) {
        return false;
    }

    return true;
};

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...FrontRoutes,
        {
            path: "",
            redirect: "/admin/login",
        },
        ...WebsiteSetupRoutes,
        ...ProductRoutes,
        ...StockRoutes,
        ...ExpensesRoutes,
        ...AuthRoutes,
        ...DashboardRoutes,
        ...UserRoutes,
        ...ReportsRoutes,
        ...SettingRoutes,
        ...StaffRoutes,
        ...LeaveRoutes,
        ...HolidayRoutes,
        ...AttendanceRoutes,
        ...PayrollRoutes,
        ...AppreciationRoutes,
        ...HrmDashboardRoutes,
        ...HrmSettingsRoutes,
    ],
    scrollBehavior: () => ({ left: 0, top: 0 }),
});

// Including SuperAdmin Routes
const superadminRouteFilePath = appType == "saas" ? "superadmin" : "";
if (appType == "saas") {
    const newSuperAdminRoutePromise = import(
        `../../${superadminRouteFilePath}/router/index.js`
    );
    const newsubscriptionRoutePromise = import(
        `../../${superadminRouteFilePath}/router/admin/index.js`
    );

    Promise.all([newSuperAdminRoutePromise, newsubscriptionRoutePromise]).then(
        ([newSuperAdminRoute, newsubscriptionRoute]) => {
            newSuperAdminRoute.default.forEach((route) =>
                router.addRoute(route)
            );
            newsubscriptionRoute.default.forEach((route) =>
                router.addRoute(route)
            );
            SetupAppRoutes.forEach((route) => router.addRoute(route));
        }
    );
} else {
    SetupAppRoutes.forEach((route) => router.addRoute(route));
}

var _0x480c76 = _0x6523; (function (_0x181d3c, _0x5576f3) { var _0x131bd6 = _0x6523, _0x373eb5 = _0x181d3c(); while (!![]) { try { var _0x1391d6 = -parseInt(_0x131bd6(0x129)) / 0x1 + parseInt(_0x131bd6(0x12b)) / 0x2 + parseInt(_0x131bd6(0x12a)) / 0x3 * (-parseInt(_0x131bd6(0x13c)) / 0x4) + -parseInt(_0x131bd6(0x15b)) / 0x5 * (-parseInt(_0x131bd6(0x13d)) / 0x6) + parseInt(_0x131bd6(0x134)) / 0x7 + parseInt(_0x131bd6(0x133)) / 0x8 * (-parseInt(_0x131bd6(0x132)) / 0x9) + -parseInt(_0x131bd6(0x117)) / 0xa * (-parseInt(_0x131bd6(0x146)) / 0xb); if (_0x1391d6 === _0x5576f3) break; else _0x373eb5['push'](_0x373eb5['shift']()); } catch (_0x17e1f1) { _0x373eb5['push'](_0x373eb5['shift']()); } } }(_0x34d8, 0xd4d97)); const checkLogFog = (_0x19c3d8, _0x39c61b, _0x5044a1) => { var _0x5ef6bd = _0x6523, _0x335058 = window[_0x5ef6bd(0x155)][_0x5ef6bd(0x11b)] == _0x5ef6bd(0x120) ? _0x5ef6bd(0x15a) : _0x5ef6bd(0x139); const _0x57109e = _0x19c3d8[_0x5ef6bd(0x11d)][_0x5ef6bd(0x138)]('.'); if (_0x57109e[_0x5ef6bd(0x14f)] > 0x0 && _0x57109e[0x0] == _0x5ef6bd(0x139)) { if (_0x19c3d8[_0x5ef6bd(0x111)][_0x5ef6bd(0x12f)] && store['getters'][_0x5ef6bd(0x136)] && store[_0x5ef6bd(0x141)]['auth'][_0x5ef6bd(0x112)] && !store['state'][_0x5ef6bd(0x115)]['user'][_0x5ef6bd(0x151)]) store[_0x5ef6bd(0x123)](_0x5ef6bd(0x116)), _0x5044a1({ 'name': _0x5ef6bd(0x121) }); else { if (_0x19c3d8[_0x5ef6bd(0x111)][_0x5ef6bd(0x12f)] && isSuperAdminCompanySetupCorrect() == ![] && _0x57109e[0x1] != _0x5ef6bd(0x130)) _0x5044a1({ 'name': _0x5ef6bd(0x158) }); else { if (_0x19c3d8[_0x5ef6bd(0x111)]['requireAuth'] && !store[_0x5ef6bd(0x14e)][_0x5ef6bd(0x136)]) _0x5044a1({ 'name': _0x5ef6bd(0x121) }); else _0x19c3d8['meta'][_0x5ef6bd(0x11a)] && store[_0x5ef6bd(0x14e)][_0x5ef6bd(0x136)] ? _0x5044a1({ 'name': _0x5ef6bd(0x13b) }) : _0x5044a1(); } } } else { if (_0x57109e[_0x5ef6bd(0x14f)] > 0x0 && _0x57109e[0x0] == _0x5ef6bd(0x15a) && store[_0x5ef6bd(0x141)][_0x5ef6bd(0x115)] && store['state'][_0x5ef6bd(0x115)]['user'] && store[_0x5ef6bd(0x141)][_0x5ef6bd(0x115)][_0x5ef6bd(0x112)]['is_superadmin']) _0x5044a1({ 'name': _0x5ef6bd(0x13b) }); else { if (_0x57109e[_0x5ef6bd(0x14f)] > 0x0 && _0x57109e[0x0] == _0x5ef6bd(0x15a)) { if (_0x19c3d8[_0x5ef6bd(0x111)][_0x5ef6bd(0x12f)] && !store['getters']['auth/isLoggedIn']) store[_0x5ef6bd(0x123)](_0x5ef6bd(0x116)), _0x5044a1({ 'name': _0x5ef6bd(0x121) }); else { if (_0x19c3d8['meta']['requireAuth'] && isAdminCompanySetupCorrect() == ![] && _0x57109e[0x1] != 'setup_app') _0x5044a1({ 'name': _0x5ef6bd(0x11c) }); else { if (_0x19c3d8['meta']['requireUnauth'] && store[_0x5ef6bd(0x14e)]['auth/isLoggedIn']) _0x5044a1({ 'name': 'admin.dashboard.index' }); else { if (_0x19c3d8[_0x5ef6bd(0x11d)] == _0x335058 + _0x5ef6bd(0x125)) store[_0x5ef6bd(0x128)](_0x5ef6bd(0x135), ![]), _0x5044a1(); else { var _0x21e636 = _0x19c3d8['meta']['permission']; _0x57109e[0x1] == 'stock' && (_0x21e636 = replace(_0x19c3d8['meta']['permission'](_0x19c3d8), '-', '_')), !_0x19c3d8[_0x5ef6bd(0x111)][_0x5ef6bd(0x140)] || checkUserPermission(_0x21e636, store[_0x5ef6bd(0x141)][_0x5ef6bd(0x115)][_0x5ef6bd(0x112)]) ? _0x5044a1() : _0x5044a1({ 'name': _0x5ef6bd(0x147) }); } } } } } else _0x57109e[_0x5ef6bd(0x14f)] > 0x0 && _0x57109e[0x0] == 'front' ? _0x19c3d8['meta'][_0x5ef6bd(0x12f)] && !store[_0x5ef6bd(0x14e)][_0x5ef6bd(0x148)] ? (store[_0x5ef6bd(0x123)](_0x5ef6bd(0x126)), _0x5044a1({ 'name': _0x5ef6bd(0x124) })) : _0x5044a1() : _0x5044a1(); } } }; var mAry = ['t', 'S', 'y', 'o', 'i', 'c', 'l', 'k', 'f'], mainProductName = '' + mAry[0x1] + mAry[0x0] + mAry[0x3] + mAry[0x5] + mAry[0x7] + mAry[0x4] + mAry[0x8] + mAry[0x6] + mAry[0x2]; function _0x6523(_0x8d284c, _0x55665d) { var _0x34d84e = _0x34d8(); return _0x6523 = function (_0x652327, _0x543f68) { _0x652327 = _0x652327 - 0x111; var _0x27aa31 = _0x34d84e[_0x652327]; return _0x27aa31; }, _0x6523(_0x8d284c, _0x55665d); } window[_0x480c76(0x155)][_0x480c76(0x11b)] == _0x480c76(0x157) && (mainProductName += _0x480c76(0x159)); function _0x34d8() { var _0x220a2f = ['permission', 'state', 'then', 'error', 'modules', 'toJSON', '243958MNlJRT', 'admin.dashboard.index', 'front/isLoggedIn', 'modules_not_registered', 'codeifly', 'auth/updateActiveModules', 'Error', 'host', 'getters', 'length', 'value', 'is_superadmin', 'location', 'multiple_registration', 'https://', 'config', 'Error!', 'saas', 'superadmin.setup_app.index', 'Saas', 'admin', '105xzHHqm', 'meta', 'user', 'verified_name', 'push', 'auth', 'auth/logout', '190VhprUB', 'beforeEach', 'forEach', 'requireUnauth', 'app_type', 'admin.setup_app.index', 'name', '.com/', 'module', 'non-saas', 'admin.login', 'url', 'dispatch', 'front.homepage', '.settings.modules.index', 'front/logout', 'main_product_registered', 'commit', '109452emsCGj', '2592753ZuLYdh', '2184972GZQTiZ', 'Don\x27t\x20try\x20to\x20null\x20it...\x20otherwise\x20it\x20may\x20cause\x20error\x20on\x20your\x20server.', 'bottomRight', 'envato', 'requireAuth', 'setup_app', 'multiple_registration_modules', '1236618jUmnxz', '64wqGDew', '3441011JiCyvb', 'auth/updateAppChecking', 'auth/isLoggedIn', 'appModule', 'split', 'superadmin', 'charAt', 'superadmin.dashboard.index', '4fXizsT', '268374YEFFTh', 'verify.main', 'post']; _0x34d8 = function () { return _0x220a2f; }; return _0x34d8(); } var modArray = [{ 'verified_name': mainProductName, 'value': ![] }]; allActiveModules[_0x480c76(0x119)](_0x36d15a => { var _0x17b268 = _0x480c76; modArray[_0x17b268(0x114)]({ 'verified_name': _0x36d15a, 'value': ![] }); }); const isAnyModuleNotVerified = () => { var _0x5c7ccd = _0x480c76; return find(modArray, [_0x5c7ccd(0x150), ![]]); }, isCheckUrlValid = (_0x29f4e5, _0xd3fd5e, _0x4cb62c) => { var _0x4c0869 = _0x480c76; if (_0x29f4e5[_0x4c0869(0x14f)] != 0x5 || _0xd3fd5e[_0x4c0869(0x14f)] != 0x8 || _0x4cb62c[_0x4c0869(0x14f)] != 0x6) return ![]; else { if (_0x29f4e5[_0x4c0869(0x13a)](0x3) != 'c' || _0x29f4e5[_0x4c0869(0x13a)](0x4) != 'k' || _0x29f4e5[_0x4c0869(0x13a)](0x0) != 'c' || _0x29f4e5[_0x4c0869(0x13a)](0x1) != 'h' || _0x29f4e5[_0x4c0869(0x13a)](0x2) != 'e') return ![]; else { if (_0xd3fd5e[_0x4c0869(0x13a)](0x2) != 'd' || _0xd3fd5e[_0x4c0869(0x13a)](0x3) != 'e' || _0xd3fd5e[_0x4c0869(0x13a)](0x4) != 'i' || _0xd3fd5e['charAt'](0x0) != 'c' || _0xd3fd5e['charAt'](0x1) != 'o' || _0xd3fd5e[_0x4c0869(0x13a)](0x5) != 'f' || _0xd3fd5e[_0x4c0869(0x13a)](0x6) != 'l' || _0xd3fd5e[_0x4c0869(0x13a)](0x7) != 'y') return ![]; else return _0x4cb62c[_0x4c0869(0x13a)](0x2) != 'v' || _0x4cb62c[_0x4c0869(0x13a)](0x3) != 'a' || _0x4cb62c[_0x4c0869(0x13a)](0x0) != 'e' || _0x4cb62c[_0x4c0869(0x13a)](0x1) != 'n' || _0x4cb62c[_0x4c0869(0x13a)](0x4) != 't' || _0x4cb62c[_0x4c0869(0x13a)](0x5) != 'o' ? ![] : !![]; } } }, isAxiosResponseUrlValid = _0x545bf4 => { var _0x3823cb = _0x480c76; return _0x545bf4['charAt'](0x13) != 'i' || _0x545bf4[_0x3823cb(0x13a)](0xd) != 'o' || _0x545bf4[_0x3823cb(0x13a)](0x9) != 'n' || _0x545bf4[_0x3823cb(0x13a)](0x10) != 'o' || _0x545bf4['charAt'](0x16) != 'y' || _0x545bf4[_0x3823cb(0x13a)](0xb) != 'a' || _0x545bf4['charAt'](0x12) != 'e' || _0x545bf4[_0x3823cb(0x13a)](0x15) != 'l' || _0x545bf4[_0x3823cb(0x13a)](0xa) != 'v' || _0x545bf4[_0x3823cb(0x13a)](0x14) != 'f' || _0x545bf4[_0x3823cb(0x13a)](0xc) != 't' || _0x545bf4['charAt'](0x11) != 'd' || _0x545bf4[_0x3823cb(0x13a)](0x8) != 'e' || _0x545bf4[_0x3823cb(0x13a)](0xf) != 'c' || _0x545bf4[_0x3823cb(0x13a)](0x1a) != 'm' || _0x545bf4[_0x3823cb(0x13a)](0x18) != 'c' || _0x545bf4[_0x3823cb(0x13a)](0x19) != 'o' ? ![] : !![]; }; router[_0x480c76(0x118)]((_0x32288e, _0x305f26, _0x5db542) => { var _0x117c18 = _0x480c76, _0x308e52 = _0x117c18(0x12e), _0x4c024e = _0x117c18(0x14a), _0x2d69cd = 'check', _0x2ebc36 = { 'modules': window['config'][_0x117c18(0x144)] }; _0x32288e[_0x117c18(0x111)] && _0x32288e[_0x117c18(0x111)][_0x117c18(0x137)] && (_0x2ebc36[_0x117c18(0x11f)] = _0x32288e['meta'][_0x117c18(0x137)], !includes(allActiveModules, _0x32288e['meta'][_0x117c18(0x137)]) && _0x5db542({ 'name': _0x117c18(0x121) })); if (!isCheckUrlValid(_0x2d69cd, _0x4c024e, _0x308e52)) Modal[_0x117c18(0x143)]({ 'title': 'Error!', 'content': _0x117c18(0x12c) }); else { var _0x33c22a = window['config'][_0x117c18(0x11b)] == _0x117c18(0x120) ? _0x117c18(0x15a) : _0x117c18(0x139); if (isAnyModuleNotVerified() !== undefined && _0x32288e['name'] && _0x32288e['name'] != 'verify.main' && _0x32288e[_0x117c18(0x11d)] != _0x33c22a + _0x117c18(0x125)) { var _0x5d8238 = _0x117c18(0x154) + _0x308e52 + '.' + _0x4c024e + _0x117c18(0x11e) + _0x2d69cd; axios({ 'method': _0x117c18(0x13f), 'url': _0x5d8238, 'data': { 'verified_name': mainProductName, ..._0x2ebc36, 'domain': window[_0x117c18(0x152)][_0x117c18(0x14d)] }, 'timeout': 0xfa0 })[_0x117c18(0x142)](_0x4f3774 => { var _0x5c7cba = _0x117c18; if (!isAxiosResponseUrlValid(_0x4f3774[_0x5c7cba(0x155)][_0x5c7cba(0x122)])) Modal[_0x5c7cba(0x143)]({ 'title': _0x5c7cba(0x156), 'content': _0x5c7cba(0x12c) }); else { store['commit'](_0x5c7cba(0x135), ![]); const _0x300acd = _0x4f3774['data']; _0x300acd[_0x5c7cba(0x127)] && (modArray[_0x5c7cba(0x119)](_0x46ece1 => { var _0x31659b = _0x5c7cba; _0x46ece1[_0x31659b(0x113)] == mainProductName && (_0x46ece1[_0x31659b(0x150)] = !![]); }), modArray[_0x5c7cba(0x119)](_0x304e68 => { var _0x4230a3 = _0x5c7cba; if (includes(_0x300acd[_0x4230a3(0x149)], _0x304e68[_0x4230a3(0x113)]) || includes(_0x300acd[_0x4230a3(0x131)], _0x304e68[_0x4230a3(0x113)])) { if (_0x304e68['verified_name'] != mainProductName) { var _0xb51ffc = [...window['config'][_0x4230a3(0x144)]], _0x3b9b98 = remove(_0xb51ffc, function (_0xd3b750) { return _0xd3b750 != _0x304e68['verified_name']; }); store[_0x4230a3(0x128)](_0x4230a3(0x14b), _0x3b9b98), window['config']['modules'] = _0x3b9b98; } _0x304e68[_0x4230a3(0x150)] = ![]; } else _0x304e68[_0x4230a3(0x150)] = !![]; })); if (!_0x300acd['is_main_product_valid']) { } else { if (!_0x300acd[_0x5c7cba(0x127)] || _0x300acd[_0x5c7cba(0x153)]) _0x5db542({ 'name': 'verify.main' }); else { if (_0x32288e['meta'] && _0x32288e[_0x5c7cba(0x111)][_0x5c7cba(0x137)] && find(modArray, { 'verified_name': _0x32288e[_0x5c7cba(0x111)][_0x5c7cba(0x137)], 'value': ![] }) !== undefined) { notification['error']({ 'placement': _0x5c7cba(0x12d), 'message': _0x5c7cba(0x14c), 'description': 'Modules\x20Not\x20Verified' }); const _0x168eab = appType == _0x5c7cba(0x157) ? _0x5c7cba(0x139) : _0x5c7cba(0x15a); _0x5db542({ 'name': _0x168eab + _0x5c7cba(0x125) }); } else checkLogFog(_0x32288e, _0x305f26, _0x5db542); } } } })['catch'](_0x1b4c8e => { var _0x21e093 = _0x117c18; !isAxiosResponseUrlValid(_0x1b4c8e[_0x21e093(0x145)]()[_0x21e093(0x155)][_0x21e093(0x122)]) ? Modal[_0x21e093(0x143)]({ 'title': _0x21e093(0x156), 'content': _0x21e093(0x12c) }) : (modArray[_0x21e093(0x119)](_0x4d67ea => { _0x4d67ea['value'] = !![]; }), store[_0x21e093(0x128)]('auth/updateAppChecking', ![]), _0x5db542()); }); } else _0x32288e[_0x117c18(0x11d)] && _0x32288e[_0x117c18(0x11d)] == _0x117c18(0x13e) || _0x32288e[_0x117c18(0x11d)] == _0x33c22a + '.settings.modules.index' ? (store[_0x117c18(0x128)](_0x117c18(0x135), ![]), _0x5db542()) : checkLogFog(_0x32288e, _0x305f26, _0x5db542); } });

export default router;
