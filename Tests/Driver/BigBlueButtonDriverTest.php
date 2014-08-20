<?php

namespace ElanEv\Tests;

use ElanEv\Driver\BigBlueButtonDriver;
use ElanEv\Driver\JoinParameters;
use ElanEv\Driver\MeetingParameters;
use Guzzle\Http\ClientInterface;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class BigBlueButtonDriverTest extends AbstractDriverTest
{
    private $salt = '8eea0fdb387a787b23cbfe98ad942012';

    /**
     * {@inheritdoc}
     */
    public function getCreateMeetingData()
    {
        $parameters = new MeetingParameters();
        $parameters->setMeetingId('meeting-id');
        $parameters->setMeetingName('meeting-name');
        $parameters->setAttendeePassword('attendee-password');
        $parameters->setModeratorPassword('moderator-password');
        $urlParameters = array(
            'name' => 'meeting-name',
            'meetingID' => 'meeting-id',
            'attendeePW' => 'attendee-password',
            'moderatorPW' => 'moderator-password',
            'dialNumber' => '',
            'webVoice' => '',
            'logoutURL' => '',
            'maxParticipants' => '-1',
            'record' => 'false',
            'duration' => '0',
            'checksum' => '219b59cb12295d446af67a38cfd02b7451b28adc',
        );

        return array(
            'create-existing-room' => array(
                $parameters,
                array(array(
                    'method' => 'get',
                    'uri' => 'api/create?'.http_build_query($urlParameters),
                    'response' => $this->getDuplicateWarningMessage(),
                )),
                true,
            ),
            'checksum-check-failed' => array(
                $parameters,
                array(array(
                    'method' => 'get',
                    'uri' => 'api/create?'.http_build_query($urlParameters),
                    'response' => $this->getChecksumCheckFailedMessage(),
                )),
                false,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMeetingRunningData()
    {
        $meetingId1 = '788b98f571322bb3a471c8f2926fce9b';
        $meetingId2 = '687c258d96bd340c4aa09bfe67842d40';
        $meetingId3 = '1b0ac2b59cc262b1651c0b8d7a53c7ba';

        return array(
            'meeting-running' => array(
                $meetingId1,
                array(array(
                    'method' => 'get',
                    'uri' => 'api/isMeetingRunning?meetingID='.$meetingId1.'&checksum=2ebbbc94d0ca360b02b2a83ceeed1112b60bd90e',
                    'response' => '<response><returncode>SUCCESS</returncode><running>true</running></response>',
                )),
                true,
            ),
            'meeting-not-running' => array(
                $meetingId2,
                array(array(
                    'method' => 'get',
                    'uri' => 'api/isMeetingRunning?meetingID='.$meetingId2.'&checksum=6a9941fb0cb3f7df87626dd5d741ca4247ccce6c',
                    'response' => '<response><returncode>SUCCESS</returncode><running>false</running></response>',
                )),
                false,
            ),
            'checksum-check-failed' => array(
                $meetingId3,
                array(array(
                    'method' => 'get',
                    'uri' => 'api/isMeetingRunning?meetingID='.$meetingId3.'&checksum=d3f5a816abad3d40f7880b619dc00cbb8170a895',
                    'response' => $this->getChecksumCheckFailedMessage(),
                )),
                false,
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGetJoinMeetingUrlData()
    {
        $parameters = new JoinParameters();
        $parameters->setMeetingId('280c0c8c16220807126f8787f24bf949');
        $parameters->setUsername('the-username');
        $parameters->setPassword('the-password');

        $urlParameters = array(
            'meetingID' => '280c0c8c16220807126f8787f24bf949',
            'fullName' => 'the-username',
            'password' => 'the-password',
            'userID' => '',
            'webVoiceConf' => '',
            'checksum' => 'b04523cca534cfcca964347f4c00f634ba0ae0e4',
        );

        return array(array($parameters, 'http://example.com/api/join?'.http_build_query($urlParameters)));
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(ClientInterface $client)
    {
        return new BigBlueButtonDriver($client, $this->salt);
    }

    private function getDuplicateWarningMessage()
    {
        return '
            <response>
                <returncode>SUCCESS</returncode>
                <meetingID>a07535cf2f8a72df33c12ddfa4b53dde</meetingID>
                <attendeePW>8ab424b8ec4fa0a2289740274f812b17</attendeePW>
                <moderatorPW>4265c155d3b13be3244f304042156050</moderatorPW>
                <createTime>1408441030997</createTime>
                <hasBeenForciblyEnded>false</hasBeenForciblyEnded>
                <messageKey>duplicateWarning</messageKey>
                <message>This conference was already in existence and may currently be in progress.</message>
            </response>
            ';
    }

    private function getChecksumCheckFailedMessage()
    {
        return '
            <response>
                <returncode>FAILED</returncode>
                <messageKey>checksumError</messageKey>
                <message>You did not pass the checksum security check</message>
            </response>
            ';
    }
}