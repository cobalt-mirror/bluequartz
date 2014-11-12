$(document).ready(function(){
    
    $(function(){
    $(".frmI").tipTip({activation: "focus",defaultPosition: "right",delay: 0, fadeIn: 600, maxWidth: "240px"});
    });
});

function getSPF()
{
    var str;
    var domain;
    var mx_O;
    var ip_O;
    var host_O;
    var ips;var ips_arr;
    var hosts;var hosts_arr;
    var domains;var domains_arr;
    var restrict_O;
    
    domain = $("#domain").val();
    mx_O = $("#mx_allow").val();
    ip_O = $("#ip_allow").val();
    host_O = $("#host_allow").val();
    ips = $("#ip_additional").val();
    hosts = $("#host_additional").val();
    domains = $("#domain_additional").val();
    restrict_O = $("#restrict").val();
    
    str = 'v=spf1';
    
    switch (mx_O)
    {
        case "0":
            break;
        case "1":
            str = str + ' mx';
            break;
    }
    
    switch (ip_O)
    {
        case "0":
            break;
        case "1":
            str = str + ' a';
            break;
    }
    
    switch (host_O)
    {
        case "0":
            break;
        case "1":
            str = str + ' ptr';
            break;
    }

    ips_arr = ips.split(" ");
    if (ips_arr.length == 1)
    {
        if (ips == "")
        {
        }
        else
        {
            str = str + ' ip4:' + ips;
        }
    }
    else
    {
        for (a=0; a<ips_arr.length; a++)
        {
            if (ips_arr[a] != "")
            {
                str = str + ' ip4:' + ips_arr[a];
            }
        }
    }
    
    hosts_arr = hosts.split(" ");
    if (hosts_arr.length == 1)
    {
        if (hosts == "")
        {
        }
        else
        {
            str = str + ' a:' + hosts;
        }
    }
    else
    {
        for (a=0; a<hosts_arr.length; a++)
        {
            if (hosts_arr[a] != "")
            {
                str = str + ' a:' + hosts_arr[a];
            }
        }
    }
    
    domains_arr = domains.split(" ");
    if (domains_arr.length == 1)
    {
        if (domains == "")
        {
        }
        else
        {
            str = str + ' include:' + domains;
        }
    }
    else
    {
        for (a=0; a<domains_arr.length; a++)
        {
            if (domains_arr[a] != "")
            {
                str = str + ' include:' + domains_arr[a];
            }
        }
    }
    
    switch (restrict_O)
    {
        case "0":
            break;
        case "1":
            str = str + ' -all';
            break;
        case "2":
            str = str + ' ~all';
            break;
        case "3":
            str = str + ' ?all';
            break;
    }
            
    str = str + '';
    
    $("#dnsentry").html(str);
    
    $("#domainSP").html(domain);
}
