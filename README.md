# scripts
TASK: Have Online Users for each domain updated in Redis , so any Web GUI can read the redis and display nicely to the PBX administrators
about the online users. 
Fairly Simple task, I got in the kamailio.cfg file and right after the REGISTER Authentication I inserted User into the 
List ONLINE:$fd , since I know the Unregister packet comes with "Expires" Header value 0 so anything with this Header is supposed to be removed from the List.

    if($hdr(Expires) > 0) {
      xlog("L_NOTICE","$au REGISTER Authenticated UserStatus:Online Expiry:'$hdr(Expires)' Add to the Redis List\n");
      $avp(domain_list) = "ONLINE:" + $fd;
      redis_cmd("srvN", "SADD $avp(domain_list) $au", "r");
    }
    if ($hdr(Expires) == 0){
     xlog("L_NOTICE","$au UN-REGISTER Authenticated UserStatus:Offline Expiry:'$hdr(Expires)' Remove from the Redis List\n");
     $avp(domain_list) = "ONLINE:" + $fd;
     redis_cmd("srvN", "SREM $avp(domain_list) $au", "r");
    }

This would work perfectly in an ideal world, Users turning their SIP clients nicely On and Off. 

In real world lots of things happen, for example, SIP Client killed due to windows crash, network disconnected, power outage and so on. In that case the Redis will have Online Users which may not be there anymore. Hence need for a way to reconcile the real online users and remove the deceased SIP users.

Example Scenario:

    redis-cli> SMEMEBERS ONLINE:abc.voip.ca
    1) "100"
    2) "101"
    3) "102"
      
User 101 got its Network cable unplugged. Now Redis should show 100, and 102 only. Since my Kamailio sends keep-alive to online users hence it detetcs quickly when 101 went dark. So Kamailio internally removes the user from its location table and memory. Redis would keep this user in the List infinitely.

To resolve this I had to query kamailio for online users, match and fix the discrepencies between the Redis and Kamailio.
Since Im not so good with pua_mi.so module and any other fancy techniques, I resolved this by taking help from the xhttp_rpc module.
xhhtp_rpc.sp module enabled me to query my Kamailio for online users "kamctl ul show" via the http/curl to fetch all the AoRs. I managed to create the script "Kamailio_xhhtp_userlocation.pl" which would parse the output from xHTTP_RPC and do some reconciliation with the Lists in Redis.

Plan is to add this perl script "Kamailio_xhhtp_userlocation.pl" in crontab and invoke every after 10 minutes or more to clear out the Redis.

  
