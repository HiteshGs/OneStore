export default [
    {
        path: "/admin/register",
        component: () => import("../views/auth/Register.vue"),
        name: "superadmin.register",
        meta: {
            requireUnauth: true,
            menuKey: (route) => "register",
        },
    },
];
