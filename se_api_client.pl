#!/usr/bin/env perl
use warnings;
use strict;

use LWP;
use HTTP::Date; #optional: time2iso
use JSON;
use Data::Dumper;
use Getopt::Std;
use Digest::SHA qw(hmac_sha256_base64); 

###############################################################################
###############################################################################
##                           se_api_client.pl                                ##
## Sample API client in perl.  Does nothing by itself, but should serve as a ##
## good starting point.  Has a few channel specific functions already.       ##
## Please consult the documentation at https://cp.scaleengine.net/docs       ##
## Written by Andrew Fengler                                                 ##
###############################################################################
## Copyright (c) 2016, Andrew Fengler <andrew.fengler@scaleengine.com>       ##
##                                                                           ##
## Permission to use, copy, modify, and/or distribute this software for any  ##
## purpose with or without fee is hereby granted, provided that the above    ##
## copyright notice and this permission notice appear in all copies.         ##
##                                                                           ##
## THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES  ##
## WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF          ##
## MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR   ##
## ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES    ##
## WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN     ##
## ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF   ##
## OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE             ##
###############################################################################
## V. 1.0.0: Initial release                                        20170125 ##
###############################################################################
my $version = '1.0.0';
my $version_date = '2017-01-25';


###############################################################################
## Global variables
###############################################################################

my %conf = (
	debug			=> 0,
	api_url			=> 'https://api.scaleengine.net/dev/',
);
my %opt;
my @videos = ();
my %channel = (
	name			=> '',
	cdn			=> '',
	api_key			=> '',
	api_secret		=> '',
	active			=> 1, #sane default
	repeat			=> 1, #sane default
	channel_id		=> '',
	metadata		=> 0, #will copy existing,
	stream			=> '',
	run_on			=> 'hourly',
	time_offset		=> 0,
);


###############################################################################
## Subroutine definitions
###############################################################################

sub HELP_MESSAGE
{
	print "$0 usage:\n\t$0 [-v|-d] ..\n";
	exit;
}

sub VERSION_MESSAGE
{
	print "$0 version $version by Andrew Fengler (andrew.fengler\@scaleengine.net), $version_date\n";
	exit;
}

sub verb
{
	local $, = ' ';
	local $\ = "\n";
	shift @_ <= $conf{debug} and print STDERR @_;
	return 1;
}

sub test_response ($)
{
	if ( $_[0] == 0 )
	{
		print "Error: API call failed\n";
		return 0;
	}
	if ( $_[0]->{status} eq 'success' )
	{
		return 1;
	}
	elsif ( $_[0]->{status} eq 'failure' )
	{
		print "Error: unable to talk to API - response was: $_[0]->{message}\n";
		defined $_[0]->{debug} and verb 1, $_[0]->{debug};
		defined $_[0]->{debug2} and verb 2, $_[0]->{debug2};
		return 0;
	}
	print "Unrecognised response: $_[0]->{status}\n";
	return 0;
}

sub api_call ($)
{
	my $params = $_[0];
	my ( $sig, $data_json, $browser, $response, $response_clean, $error_msg );

	#Build up json of parameters
	$params->{timestamp} = time();
	$data_json = encode_json($params);
	$sig = hmac_sha256_base64($data_json, $channel{api_secret});
	#pad base64 to a multiple of 4
	while (length($sig) % 4) { $sig .= '='; }
	#$params->{signature} = $sig;
	#$data_json = encode_json($params);
	#Perl doesn't keep hashes in order
	$data_json =~ s/(.*?)}$/$1,"signature":"$sig"}/;
	verb 2, "Requesting: ", $data_json;

	$browser = LWP::UserAgent->new();
	$response = $browser->post($conf{api_url}, {'json' => $data_json});
	if ( $response->is_success )
	{
		verb 2, $response->content;
		$response_clean = $response->content;
		return decode_json($response_clean);
	}
	else
	{
		verb 1, "ERROR: POST responded ", $response->status_line; 
		$error_msg = $response->status_line;
		return 0;
	}
}

sub find_channel
{
	#check that chanel details check out
	my %api_params;
	my $api_response;
	verb 1, "checking channel...";

	%api_params = (
		'command'	=> 'channel.getallchannels',
		'api_key'	=> $channel{api_key},
		'cdn'		=> $channel{cdn},
	);
	
	$api_response = api_call(\%api_params);
	verb 2, Dumper $api_response;

	unless ( test_response($api_response) ) { return 0; }

	LISTS: for my $list (@{$api_response->{data}})
	{
		#find the matching list
		if ( $list->{channel_name} =~ /^$channel{name}$/ )
		{
			verb 1, "Found list pair $1: \n", Dumper $list;
			#extract channel_id, stream, and metadata
			$channel{metadata} = $list->{metadata};
			$channel{channel_id} = $list->{channel_id};
			$channel{stream} = $list->{stream};
			$channel{scheduled} = $list->{scheduled};
		}
	}

	return 1;
}

sub update_channel
{
	my %api_params;
	my $api_response;
	verb 1, "Updating channel...";

	# Update videos
	verb 1, "Updating videos";
	%api_params = (
		'command'		=> 'channel.replacevideos',
		'api_key'		=> $channel{api_key},
		'cdn'			=> $channel{cdn},
		'channel_id'	=> $channel{channel_id},
		'videos'		=> \@videos,
	);
	print Dumper @videos;

	$api_response = api_call(\%api_params);
	unless ( test_response($api_response) ) { return 0; }

	#Update scheduling:
	verb 1, "Updating schedule";
	%api_params = (
		'command'		=> 'channel.updatechannel',
		'api_key'		=> $channel{api_key},
		'cdn'			=> $channel{cdn},
		'channel_id'	=> $channel{channel_id},
		'stream'		=> $channel{stream},
		'metadata'		=> $channel{metadata},
		'name'			=> $channel{name},
		'repeat'		=> $channel{repeat},
		'active'		=> $channel{active},
		'scheduled'		=> $channel{scheduled},
	);
	
	$api_response = api_call(\%api_params);
	unless ( test_response($api_response) ) { return 0; }

	return 1;
}


##############################################################################
## Main program
##############################################################################


#check channel
if ( find_channel() )
{
	verb 1, "Channel successfully verified: ";
}
else
{
	die "Channel verification failed!  Check that your settings are correct!\n";
}

#Run api call
if ( update_channel() )
{
	exit;
}
else
{
	die "API call failed!\n";
}
