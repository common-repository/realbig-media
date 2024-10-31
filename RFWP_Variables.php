<?php

if (!defined("ABSPATH")) {exit;}

if (!class_exists('RFWP_Variables')) {
    class RFWP_Variables {
       const LOCAL_ROTATOR_GATHER = "localRotatorGatherTimeout";

       const GATHER_CONTENT_LONG = "gatherContentContainerLong";
       const GATHER_CONTENT_SHORT = "gatherContentContainerShort";

       const CUSTOM_SYNC = "rb_customSyncUsed";


        const CSRF_ACTION = "rfwp_admin_page";
        const CSRF_USER_JS_ACTION = "rfwp_user_js";
    }
}