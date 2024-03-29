#!/usr/bin/perl

#use strict;
#use warnings;

use HTTPD::Realm 1.5
$VERSION = 1.53;

##############################################################
# User interface:
# Called with the name of an existing (normal) user, allows
# the user to set his password.
# The user must already be authenticated and in the password file
# in order for this to work.
#
# When called with the ID of a user in the special "administrators"
# group, presents an interface which allows adding, deleting, and
# modifying passwords of other users, as well as adding users to
# particular groups.
#
# See user_manage.html for detailed documentation.
#
# Copyright 1997, Lincoln D. Stein.  All rights reserved.
# See the accompanying HTML file for usage and distribution
# information.  The master version can be found at:
# http://www.genome.wi.mit.edu/~lstein/user_manage/
##############################################################


# >>>>>>>>>>>>>>>>>> SITE-SPECIFIC GLOBALS <<<<<<<<<<<<<<<<<
# >>>>>>>> THESE MUST BE MODIFIED TO SUIT YOUR SITE <<<<<<<<

# Path to our configuration file.  Change as appropriate for
# your site.
$CONFIG_FILE = '/usr/local/apache/conf/realms.conf';

# Set this to the name of your server.  Only 'apache' is guaranteed
# to work. 'ncsa' and 'netscape' might work too -- you'll have to try.
$SERVER = 'apache';

# Name of the administrators' group.  When members of this group
# call up this script, they will be able to create and edit other
# users.  Set to an empty string to disable this feature.
$ADMIN_GROUP = 'administrators';

# Set this to the default group for new users, or an empty string
# if you don't want there to be any.
$DEFAULT_GROUP = 'duty';

# Set this to "1" to require the script to be under
# server access control.
$REQUIRE_ACCESS_CONTROL = 1;

# Set this to "1" to require the script to perform its own
# access control, regardless of whether it is under server
# access control.
$USE_OWN_ACCESS_CONTROL = 0;

# By default, the password and group files are set to be world-readable,
# owner writable (-rw-r--r--).  You may wish to change this to group-writable
# if you wish to make this script set-gid.
# e.g. $CREATE_MODE = 0664;
$CREATE_MODE = 0664; # -rw-rw-r-- 

# If you are using this script from the command line, you
# may need to change $STTY to point to the position of the 
# 'stty' program on your system (it's used to turn off line echo
# when entering passwords.)
$STTY = '/bin/stty';

###########################################################################
# ------------------- NO USER SERVICEABLE PARTS BELOW ---------------------
$ENV{PATH} = '/bin:/usr/bin';
$ENV{IFS} = '';
$MAX_SCROLL = 5;

BEGIN {
    if ($ENV{REQUEST_METHOD}) {
        require CGI;
        CGI->import(qw(:standard :html3 font));
        require CGI::Carp;
        CGI::Carp->import('fatalsToBrowser');
    }
}

$REALMS = new HTTPD::Realm(-config_file=>$CONFIG_FILE,-mode=>$CREATE_MODE,-server=>$SERVER);
die "Couldn't read configuration file" unless $REALMS;

$DEFAULT_REALM = $REALMS->realm(); # calling realm() without arguments returns default

if (!$ENV{REQUEST_METHOD}) {
    &dbm_manage;
    exit 0;
}

import_names('Q');
$Q::realm = $DEFAULT_REALM->name() unless $Q::realm;
$referer = '' || $Q::referer || referer();
$url = '' || url();

# print the HTTP header.
print header(),
    start_html('Change Password');

if (defined($Q::action) && $Q::action eq 'about') {
    about();
    exit 0;
}

# Unless the user has authenticated himself, object.
$user = remote_user();

if ($REQUIRE_ACCESS_CONTROL and !$user) {
    error_msg('No Authorization',
      'This script can only be accessed by users who have authenticated themselves.  ',
      'Please place this script under authentication restrictions (both GET and POST) and try again.');
    exit 0;
}

undef($user) if $USE_OWN_ACCESS_CONTROL;

# Check the configuration and object if not defined.
unless ($REALMS->exists($Q::realm)) {
    error_msg('Invalid Realm',
      "The provided password/group configuration, <strong>$Q::realm</strong>, is undefined.  ",
      'Please define the configuration and try again.');
    exit 0;
}

# Attempt to open the database.
unless ( $db = $REALMS->dbm(-realm=>$Q::realm) ) {
    error_msg('Invalid File',
      "Realm ",strong($Q::realm)," could not be opened: ",
      em( HTTPD::RealmManager->error() ) );
    exit 0;
}

# If no user is defined by access control, then prompt for it.
$user = get_user_from_params($db) unless $user;
unless ($user) {
    &print_tail;
    exit 0;
}

# Make sure that the user is in the database.
unless ($db->passwd($user)) {
    error_msg('Invalid User',
      "The user named \"$user\" is not found within the $Q::realm password file. ",
      "Permission denied.");
    exit 0;
}

# See if this user is in the magic group.
if ($ADMIN_GROUP && $db->match_group(-user => $user, -group => $ADMIN_GROUP)) {
    do_administration($db);
    exit 0;
}

# At this point everything seems to be copascetic, so we can present the
# password changing screen.

if (defined($Q::password1) && defined($Q::password2) && 
    $Q::password1 && $Q::password2) {
    &change_password ($db,$user,$Q::password1,$Q::password2);
} else {
    &print_password_prompt;
}

&print_tail;

sub print_password_prompt {
    print h1("Change password for $user"),
    'Type your new password into both text fields and press "Change"',
    p(),
    start_form(),
        table(
          TR(
         th("New Password"),
         td(password_field('password1'))
         ),
          TR(
         th("Type it again"),
         td(password_field('password2')),
         td(submit(-name=>'action',-value=>'Change'))
         )
          );
    print hidden(-name=>'referer',-value=>$referer) if $referer;
    print hidden(-name=>'realm',-value=>defined($Q::realm) ? $Q::realm : 'default');
    print hidden(-name=>'user',-default=>$user);
    print hidden(-name=>'passwd',-default=>'');
    print end_form();
}

sub change_password {
    my ($db,$user,$password1,$password2) = @_;

    unless ($password1 eq $password2) {
        error_msg('Password Mismatch', "The two passwords don't match. ", "Please retype them.");
        print hr();
        return;
    }

    # If we get here then it's OK to change the password.
    if ($db->set_passwd(-user=>$user,-passwd=>$password1)) {
        print h2('Password changed'),
            "Password for $user has been changed.",
            hr();
    } else {
        print h2('Error changing password'),
            "An error occurred while changing your password. ",
            "Please try again.",
        hr();
        warn HTTPD::RealmManager::error();
    }
}

sub print_tail {
    my $url = url();
###    print a({href=>$referer},"Exit the password changing pages")
    print a({href=>"/review"},"Exit the password changing pages") if $referer;
    print hr(),
        a({href=>"$url?action=about"},"About this script"),
        end_html();
}

sub get_user_from_params {
    my $db = shift;
    my $user = $Q::admin || $Q::user;
    my $passwd = $Q::passwd;
    if ($user && $passwd) {
        return $user if $db->match_passwd(-user=>$user,-passwd=>$passwd);
        error_msg('Authentication Error',
          'The user name and/or password you entered was incorrect.  ',
          'Please try again.');
        print hr();
    }

    print h1('Enter Current Password'),
        'Enter your current user name and password, then press ',em("Submit"),
    start_form(),
    table(
         TR(
        th('Name'),
        td(textfield(-name=>'user',
                 -default=>user_name()))
        ),
         TR(
        th('Password'),
        td(password_field(-name=>'passwd')),
        td(submit(-name=>'action',-value=>'Submit'))
        )
         );
    print hidden(-name=>'referer',-value=>$referer) if $referer;
    print hidden(-name=>'realm',-value=>defined($Q::realm) ? $Q::realm : 'default');
    print end_form();

    return undef;
}

sub about {
    $url=~s/action=about//;
    print h1('About change_passwd'),
    "This script was written by ",a({href=>'http://www.genome.wi.mit.edu/~lstein/'},"Lincoln D. Stein"),'. ',
    "You are free to modify and redistribute it, so long as this notice remains intact. ",
    "&#169 Copyright 1997, Lincoln D. Stein.  All rights reserved.",
    hr(),
    a({href=>$url},"Change password page.");
}

sub error_msg {
    my ($head,@rest) = @_;
    print h1(font({color=>'#FF0000'},$head)),@rest;
}

# --------------- Administration screens are defined here --------------
sub do_administration {
    my $db = shift;
    $_ = '';
    $_ = $Q::action if defined($Q::action);
    
    # Because of the funny way that fields are set up, we take the
    # last member of the @user array if it is non-null.  Otherwise,
    # the first.
    my $user = $Q::user[$#Q::user] || $Q::user;

    # do different things depending on the value of the
    # "action" variable.
  SWITCH:
    {
    /edit\/add/i    and $db->passwd($user) && generate_user_list($db),
                        generate_user_page($db,$user),
                        last SWITCH;

    /delete/i       and delete_user($db,$user),
                        generate_user_list($db),
                        last SWITCH;

    /set values/i   and set_user($db,$user,$Q::password1,$Q::password2,@Q::groups)
                        &&
                        generate_user_list($db,$user),
                        generate_user_page($db,$user),
                            last SWITCH;

    # default
    generate_user_list($db);
    }
    &print_tail;
}

sub delete_user {
    my ($db,$user) = @_;
    if ($db->delete_user($user)) {
    print h1('User Deleted'),
          "The entry for user ",em($user)," was successfully deleted.",
          hr();
    return 1;
    } else {
    error_msg('Error Deleting User',
          "An error occurred while deleting user $user: ",
          em(HTTPD::RealmManager->error(),"."),
          " Please fix the error and try again. ");
    print hr();
    return undef;
    }
    
}

sub set_user {
    my($db,$user,$password1,$password2,@groups) = @_;

    # The two passwords have to match.
    unless ($password1 eq $password2) {
    error_msg('Password Mismatch',
          "The two typed passwords don't match. ",
          'Please try again.'),
    print hr();
    return undef;
    }

    # The two passwords have to be non-null.
    unless ($password1) {
    error_msg('Invalid Password',
          'The password has to be non-empty. ',
          'Please type and confirm the new password.');
    print hr();
    return undef;
    }

    # If the passwords are different from the current entry for the user, then
    # we need to set it.
    my $current = $db->passwd($user);
    if ( !$current or ( ($current ne $password1) and !$db->match_passwd(-name=>$user,-passwd=>$password1)) ) {
    my $success = $db->set_passwd(-user=>$user,-passwd=>$password1);
    unless ($success) {
        error_msg('Error Setting Password',
              "An error occurred while setting the password: ",
              em(HTTPD::RealmManager->error(),"."),
              " Please fix the error and try again. ");
        print hr();
        return undef;
    }
    }

    # If the groups are different from the current entry, then we
    # need to set it.
    my @current_groups = $db->group($user);
    @groups = sort grep($_,@groups);    # get rid of nonnull entries and sort
    if ("@current_groups" ne "@groups") {
    my $success = $db->set_group(-user=>$user,'-group'=>\@groups);
    unless ($success) {
        error_msg('Error Setting Groups',
              "An error occurred while setting the groups: ",
              em(HTTPD::RealmManager->error(),"."),
              " Please fix the error and try again.");
        print hr();
        return undef;
    }
    }

    # If the info is different from the current entry, then we need
    # to set that too.
    if (my %fields = $db->fields) {
    my $update = 0;
    my $info = $db->get_fields(-name=>$user,-fields=>[keys %fields]);
    foreach (keys %fields) {
        my $new = param("F_$_");
        undef $new if $fields{$_}=~/i/ && $new!~/^-?\d+$/;
        undef $new if $fields{$_}=~/f/ && $new!~/^-?[\dEe.]+$/;
        $update++ if defined($new)  && $new ne $info->{$_};
        $info->{$_} = $new;
    }
    $db->set_passwd(-user=>$user,-fields=>$info) if $update;
    }

    # If we get here, then all is well.
    print h1('Edit successful'),
          "The entry for user ",em($user)," was successfully updated.",
          hr();

    1;
}

sub generate_user_list {
    my $db = shift;
    my $user = shift;
    print h1("User List for Realm",em($Q::realm));
    my @users = sort $db->users();
    print start_form(),
          hidden(-name=>'referer',-value=>$referer),
          hidden(-name=>'realm',-value=>$Q::realm),
          $REQUIRE_ACCESS_CONTROL ? '' : 
          ( hidden(-name=>'admin',-value=>$Q::user),
            hidden(-name=>'passwd',-value=>'')
           ),
          table(
        TR(
           th({valign=>'TOP',align=>'RIGHT'},"Existing Users"),
           td({valign=>'TOP',align=>'LEFT',rowspan=>2},
              @users > $MAX_SCROLL ? scrolling_list(-name=>'user','-values'=>\@users,-size=>$MAX_SCROLL,
                                -default=>$Q::user[$#user]||$Q::user,
                                -override=>1)
                          : popup_menu(-name=>'user','-values'=>\@users,
                           -default=>$Q::user[$#user]||$Q::user,
                           -override=>1)
              ),
           th({valign=>'MIDDLE',align=>'RIGHT'},"New User"),
           td({valign=>'MIDDLE',align=>'LEFT'},textfield(-name=>'user',-default=>'',-override=>1,-width=>16),
              )
           ),
        TR(
           th(''),
           td(''),
           td(submit(-name=>'action',-value=>'Delete'),
              submit(-name=>'action',-value=>'Edit/Add'))
           )
        ),
      
          end_form(),
      hr();
}

sub generate_user_page {
    my $db = shift;
    my $user = shift;
    my $current_passwd = $db->passwd($user);
    my @groups = $db->group($user);
    my @all_groups = sort $db->groups();
    @groups = ($DEFAULT_GROUP) if !@groups && $DEFAULT_GROUP;
    @all_groups = ($DEFAULT_GROUP) if !@all_groups && $DEFAULT_GROUP;

    print h1($current_passwd ? "Edit User \"$user\"" : "New User \"$user\"");

    # Other fields
    if (my %fields = $db->fields) {
    my (@rows,@cells);
    my $info = $db->get_fields(-name=>$user,-fields=>[keys %fields]);
    push(@rows,th({align=>LEFT},[keys %fields]));
    foreach (keys %fields) {
        my $length;
        if ($fields{$_}=~/(\d+)/) {
        $length = $1;
        } else {
        $length = $fields{$_}=~/^[fi]$/ ? 6 : 20;
        }
        push(@cells,textfield(-name=>"F_$_",-size=>$length,-value=>ref($info) ? $info->{$_} : ''));
    }
    push(@rows,td(\@cells));
    $other_fields = strong('Other Information:') . table(TR(\@rows));
    }

    # sometimes the groups have to be unique, making this code even more complicated!
    my ($group_stuff,$data); 
    if (($data = $db->realm->SQLdata) && ($data->{usertable} eq $data->{grouptable})) {
    $group_stuff = td(popup_menu(-name=>'groups','-values'=>\@all_groups,-default=>$groups[0]));
    } else {
    $group_stuff =(@all_groups <= $MAX_SCROLL) ? td(checkbox_group(-name=>'groups','-values'=>\@all_groups,
                                           -defaults=>\@groups,-linebreak=>1))
                                           : td(scrolling_list(-name=>'groups','-values'=>\@all_groups,
                                       -size=>$MAX_SCROLL,
                                       -defaults=>\@groups,-multiple=>1)),    
    }

    print start_form(),
          hidden(-name=>'referer',-value=>$referer),
          hidden(-name=>'realm',-value=>$Q::realm),
          hidden(-name=>'user',-value=>$user),
          $REQUIRE_ACCESS_CONTROL ? '' : 
        ( hidden(-name=>'admin',-value=>$Q::user),
          hidden(-name=>'passwd',-value=>'')
         ), 
      table({-width=>'100%',-border=>''},
           TR(th(['Set Groups','Set Password'])),
           TR({-valign=>TOP},
              td(
             table({-width=>'100%'},
                   TR({-valign=>TOP},
                  $group_stuff,
                  th('Other:'),
                  td(textfield(-name=>'groups',-default=>'',-override=>1,-size=>12))
                  )
                   )
             ),
              td(
             table({-width=>'100%'},
                   TR(th('Enter:'),td(password_field(-name=>'password1',-default=>$current_passwd,-size=>12))),
                   TR(th('Confirm:'),td(password_field(-name=>'password2',-default=>$current_passwd,-size=>12)))
                   )
             )
              )
        ),
       $other_fields ? (p(),$other_fields) : (),
       reset(-value=>'Reset Values'),
       submit(-name=>'action',-value=>'Set Values'),
       end_form();

    if (0) {            # dead code
    my $back = self_url;
    $back=~s/action=[^=&]*&?//g;
    $back=~s/password[0-9]?=[^=&]*&?//g;
    $back=~s/groups=[^=&]*&?//g;
    $back=~s/user=[^=&]*&?//g;
    $back.="user=$user";
    print a({href=>$back},"List of Users");
    }
    print hr();
}

# --------------------- command line functions --------------------
# Usage: change_passwd.cgi <database> <command> <user> <value1> <value2> <value3>...
#
# commands: adduser deleteuser setgroup view
#
sub dbm_manage {

    my ($realm,$help);

    # process command line
    while ($ARGV[0] && $ARGV[0] =~ /^-/) {
    my $arg = shift @ARGV;
    $realm = shift @ARGV if $arg eq '-r';
    $help++ if $arg =~ /^(-h|--help)/i;
    }
    $realm ||= $DEFAULT_REALM;

    my($command,@rest) = @ARGV;

    my $usage = <<USAGE;
Usage: change_passwd.cgi [-r realm] <command> <user> <value1> <value2>...
       Manage Apache databases from the command line.

Arguments:
    realm     Security realm [$DEFAULT_REALM]
    command   One of "add" "delete" "edit" "group" "view" "realms" "format" "setup"

Commands:
    Name    Arguments                                        Description
    ----    ----------                                       -----------
    realms  (none)                                           List realms
    format  (none)                                           Format an access entry for the realm

    add     <user> <password> <group1,group2> <info1,info2>  Add/edit a user's password, groups, info
    edit    <user> <password> <group1,group2> <info1,info2>  Same as "add"
    delete  <user>                                           Delete a user
    group   <user> <group1> <group2>                         Assign user to named group(s)
    info    <user> <field1=value1> <field2=value2>           Edit user's other information

    view    <user>                                           Get information about user
    view    (none)                                           Dump out entire realm
    list                                                     Same as "view"
    setup                                                    Set up a new realm
USAGE
    ;
    die $usage if $help;
    die $usage if (!ref($realm) && !$realm);
    
    die "Unknown database realm \"$realm\".\n",$usage unless $REALMS->exists($realm);
    my $db;

    # don't bother opening database files for the 'format' command
    unless ($command=~/format/) {
    $db = $REALMS->dbm(-realm=>$realm,-writable=>$command=~/add|edit|delete|group|setup/i);
    die HTTPD::RealmManager->error() unless $db;
    }

    $_ = $command;
  SWITCH: 
    {
    /add|edit/i   and  do_add($db,@rest),last SWITCH;
    /delete/i     and  do_delete($db,@rest),last SWITCH;
    /realm/i      and  do_realm(),last SWITCH;
    /group/i      and  do_group($db,@rest),last SWITCH;
    /info/i       and  do_info($db,@rest),last SWITCH;
    /view|list/i  and  do_view($db,@rest),last SWITCH;
        /format/i     and  do_format( $REALMS->realm($realm) ),last SWITCH;
        /setup/i      and  do_setup( $db,$REALMS->realm($realm) ),last SWITCH;
    die $usage;
    }
}

sub do_info {
    my($db,$user,@info) = @_;
    $user = $user || prompt('User name: ');
    my (@args);

    @info = prompt("Enter comma-separated list of field=value pairs for $user: ") 
    unless @info;
    die "No info given.\n" unless @info;

    @info = map { split('\s*,\s*') } @info;

    die "$user is not in users database.\n" 
    unless my $passwd = $db->passwd($user);

    my %info = %{$db->get_fields(-name=>$user)};
    foreach (@info) {
    my($n,$v) = split('=');
    $info{$n}=$v;
    }
    print "Info successfully changed for $user.\n"
    if $db->set_passwd(-user=>$user,-passwd=>$passwd,-fields=>\%info);
}

sub do_add {
    my($db,$user,$password,$groups,$info) = @_;
    my(@args);
    $user = $user || prompt('User name: ');
    push(@args,'-user'=>$user);

    $password = $password || password_prompt();
    push(@args,'-passwd'=>$password);

    my @info = split(/[,]/,$info);
    if (@info) {
    my %info = %{$db->get_fields(-name=>$user)};
    foreach (@info) {
        my($n,$v) = split('=');
        $info{$n}=$v;
    }
    push(@args,'-fields'=>\%info);
    }

    my $current = $db->passwd($user);
    print "Password successfully changed for $user.\n"
    if $db->set_passwd(@args);

    my @groups = split(/[\s,]/,$groups);
    @groups = $DEFAULT_GROUP unless $current || @groups;
    @groups = () if $groups[0]=~/^(-|''|"")$/;
    print "Group set to @groups.\n"
    if @groups && $db->set_group(-user=>$user,-group=>\@groups);

}

sub do_delete {
    my($db,@user) = @_;
    @user = prompt('User name: ') 
    unless @user;
    my $user;
    foreach $user (@user) {
    unless ($db->passwd($user)) {
        print "$user is not in users database.\n" ;
        next;
    }
    unless ($db->delete_user($user)) {
        print "$user: delete unsuccessful.\n";
        next;
    }
    print "$user deleted.\n";
    }
}

sub do_group {
    my($db,$user,@group) = @_;
    $user = $user || prompt('User name: ');
    die "$user is not in users database.\n" unless $db->passwd($user);

    @group = prompt("Enter comma-separated list of groups for $user: ") 
    unless @group;
    die "No groups given.\n" unless @group;
    @group = map { split('\s*,\s*') } @group;
    @group = () if $group[0]=~/^(-|''|"")$/;
    
    die "Attempt to set groups failed.\n" unless $db->set_group(-user=>$user,-group=>\@group);
    print "Groups set for $user.\n";
}

sub do_view {
    my($db,@user) = @_;
    my (@list);
    if (@user) {
    @list = @user;
    } else {
    @list = sort $db->users;
    }
    foreach (@list) {
    local($user,$passwd,$fields,@groups)=($_,$db->passwd($_),$db->get_fields(-name=>$_),$db->group($_));
    $passwd = "** unknown **" unless $passwd;
    local($group) = join(",",@groups);
    local(@info,$info);
    foreach (keys %$fields) {
        push(@info,"$_=$fields->{$_}");
    }
    $info = join(',',@info);
    write;
    $- = 100;
    }
}

sub do_realm {
    $~='REALM';
    $^='REALM_TOP';
    local($realm,$name,$type,$password,$group);
    foreach (sort $REALMS->list) {
    $realm = $REALMS->realm($_);
    ($name,$type,$password,$group) =
        (
         ($_ eq "$DEFAULT_REALM" ? "*$_" : $_),
         $realm->usertype(),
         $realm->userdb(),
         $realm->groupdb()
         );
    write;
    $-=100;
    }
}

sub do_format {
    my ($realm) = shift;
    my($usertype,$grouptype,$password,$group,$crypt) = (
                            $realm->usertype(),
                            $realm->grouptype(),
                            $realm->userdb(),
                            $realm->groupdb(),
                            $realm->crypt(),
                         );
    my $dbm1=$usertype  =~ /text|file/i ? '' : $usertype;
    my $dbm2=$grouptype =~ /text|file/i ? '' : $grouptype;

    print "AuthName\t",$realm,"\n";
    print "AuthType\t",($crypt=~/MD5/i ? 'Digest' : 'Basic'),"\n";
    my $p;
    unless ($realm->usertype=~/sql/i) {
    print "Auth${dbm1}UserFile\t$password\n";
    } else {
    $p = $realm->SQLdata;
    print <<END;
Auth_MSQLHost        $p->{host}
Auth_MSQLDatabase    $p->{database}
Auth_MSQLpwd_table    $p->{usertable}
Auth_MSQLuid_field     $p->{userfield}
Auth_MSQLpwd_field    $p->{passwdfield}
END
    ;
    }
    if ($group) {
    unless ($realm->grouptype=~/sql/i) {
        print "Auth${dbm2}GroupFile\t$group\n";
    } else {
        print <<END;
Auth_MSQLgrp_table    $p->{grouptable}
Auth_MSQLgrp_field    $p->{groupfield}
END
    ;
    }
    }

    print <<END;
<Limit GET POST PUT DELETE>
require valid-user
</Limit>
END
    ;
}

sub do_setup {
    my ($dbase,$realm) = @_;
    exit 0 unless my $group = prompt_default("Pick a name for the administrative group",'administrators');
    exit 0 unless my $admin = prompt("Pick a name for the administrative account: ");
    exit 0 unless my $pass = password_prompt();

    # SQL is the hard special case
    if ($realm->usertype=~/sql/i) {
    $pass = $dbase->{userDB}->encrypt($pass);
    my($p) = $realm->SQLdata;
    my($db,$usertable,$userfield,$passwdfield,$userfieldlen,$passwdlen,$grouptable,$groupfield,$grouplen) = 
        @{$p}{qw(database usertable userfield passwdfield 
                      userfield_len passwdfield_len grouptable 
                      groupfield groupfield_len)};
    die "Malformed Users and/or Groups directive in configuration file" 
        unless $usertable && $userfield && $passwdfield;

    # pull in other fields
    my(@defs);
    if (my %fields = $dbase->fields) {
        foreach (keys %fields) {
        my($length) = $fields{$_}=~/(\d+)/;
        $length ||= 30;
        my($type) = "char($length)";
        $type = "int" if $fields{$_}=~/i/i;
        $type = "real" if $fields{$_}=~/f/i;
        push(@defs,"    $_\t" . $type);
        }
    }
    unshift(@defs,"    $groupfield\tchar($grouplen)")
        if $usertable eq $grouptable;
    my $defs = join(",\n",@defs);

    # escape single quotes
    $pass  =~   s/'/\\'/g;
    $group =~   s/'/\\'/g;
    $admin =~   s/'/\\'/g;
    $defs  =~   s/'/\\'/g;

    print STDERR "Create database $db and feed it this code:\n\n";
    print STDOUT<<END;
CREATE TABLE $usertable (
   $userfield\tchar($userfieldlen)\tprimary key,
   $passwdfield\tchar($passwdlen)\tnot null,
$defs
)\\g

INSERT INTO $usertable ($userfield,$passwdfield)
   VALUES('$admin','$pass')\\g
END
    ;
    if ($usertable eq $grouptable) {
        print STDOUT <<END;
UPDATE $usertable 
       SET $groupfield='$group' 
       WHERE $userfield='$admin'\\g
END
    ;
    } elsif ($grouptable) {
        print STDOUT <<END;
CREATE TABLE $grouptable (
   $userfield\tchar($userfieldlen),
   $groupfield\tchar($grouplen)
)\\g
INSERT INTO $grouptable ($userfield,$groupfield)
   VALUES('$admin','$group')\\g
END
    ;
    }

    }   # all nonSQL databases

    else { 
    $dbase->set_passwd(-user=>$admin,-passwd=>$pass);
    my %groups;
    grep ($groups{$_}++,$dbase->group($admin));
    $groups{$group}++;
    $dbase->set_group(-user=>$admin,-group=>[keys %groups]);
    print STDERR "Added $admin to database ",$realm->name," in group $group.\n";
    }
}

sub prompt {
    my $prompt = shift;
    my $line;
    do {
    print STDERR $prompt;
    chomp($line = <STDIN>);
    } until $line;
    return $line;
}

sub prompt_default {
    my $prompt = shift;
    my $default = shift;
    my $line;
    print STDERR "$prompt [$default]: ";
    chomp($line = <STDIN>);
    return $line || $default;
    return $line;
}

sub password_prompt {
    my $line;
    my ($pw1,$pw2);
    system "$STTY -echo </dev/tty" and die "$STTY: $!";    # turn off echo
    do {
    $pw1 = prompt("New password: ");
    $pw2 = prompt("\nRe-type new password: ");
    print STDERR "\n";
    print STDERR "The two passwords don't match. Try again.\n"
        unless $pw1 eq $pw2;
    } until $pw1 eq $pw2;
    system "$STTY echo </dev/tty";    # turn on echo
    return $pw1;
}

# These useless lines avoid "possible typo" warnings
$foo = scalar(@Q::groups);
$foo = $foo && $Q::referer && $Q::passwd;
$foo = $Q::admin && $VERSION;

format STDOUT_TOP=
Name            Password         Groups                      Info
----            --------         ------                      ----
.
format STDOUT=
^<<<<<<<<<<<<<< ^<<<<<<<<<<<<<<< ^<<<<<<<<<<<<<<<<<<<<<<<<<~ ^<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<~~
$user,$passwd,$group,$info
.

format REALM_TOP=
Name                        Type
----                        ----
.

format REALM=
@<<<<<<<<<<<<<<<<<<<<<<<<<< @<<<<<<
$name,$type
.
