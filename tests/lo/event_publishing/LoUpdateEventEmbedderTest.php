<?php

namespace go1\util\tests\lo;

use go1\util\lo\event_publishing\LoUpdateEventEmbedder;
use go1\util\lo\LoHelper;
use go1\util\lo\LoTypes;
use go1\util\Text;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/LoCreateEventEmbedderTest.php';

class LoUpdateEventEmbedderTest extends LoCreateEventEmbedderTest
{
    public function test()
    {
        $c = $this->getContainer();
        $event = LoHelper::load($this->go1, $this->eventLiId);
        $embedder = new LoUpdateEventEmbedder($this->go1, $c['access_checker'], $c['go1.client.user-domain-helper']);
        $req = Request::create('/', 'POST');
        $req->attributes->set('jwt.payload', Text::jwtContent($this->jwt));
        $embedded = $embedder->embedded($event, $req);

        $this->assertEquals('qa.mygo1.com', $embedded['portal']->title);
        $this->assertEquals(LoTypes::MODULE, $embedded['parents'][1]->type);
        $this->assertEquals(LoTypes::COURSE, $embedded['parents'][0]->type);

        $this->assertEquals(1, sizeof($embedded['authors']));
        $author = $embedded['authors'][0];
        $this->assertEquals(1, $author['id']);
        $this->assertEquals('john.doe@qa.local', $author['mail']);
        $this->assertEquals('A', $author['first_name']);
        $this->assertEquals('T', $author['last_name']);
    }
}
