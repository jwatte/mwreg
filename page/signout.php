<?php

setcookie('session', null, 0, "$ROOTPATH/", $COOKIEHOST, false, true);
header("Location: $ROOTPATH/");
