<?php

namespace OfflineAgency\LaravelFattureInCloudV2\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\MessageBag;
use OfflineAgency\LaravelFattureInCloudV2\Api\ReceivedDocument;
use OfflineAgency\LaravelFattureInCloudV2\Entities\Error;
use OfflineAgency\LaravelFattureInCloudV2\Entities\ReceivedDocument\ReceivedDocument as ReceivedDocumentEntity;
use OfflineAgency\LaravelFattureInCloudV2\Entities\ReceivedDocument\ReceivedDocumentGetExistingTotals;
use OfflineAgency\LaravelFattureInCloudV2\Entities\ReceivedDocument\ReceivedDocumentList;
use OfflineAgency\LaravelFattureInCloudV2\Entities\ReceivedDocument\ReceivedDocumentPagination;
use OfflineAgency\LaravelFattureInCloudV2\Entities\ReceivedDocument\ReceivedDocumentPreCreateInfo;
use OfflineAgency\LaravelFattureInCloudV2\Tests\Fake\ReceivedDocumentFakeResponse;
use OfflineAgency\LaravelFattureInCloudV2\Tests\TestCase;


class ReceivedDocumentEntityTest extends TestCase
{
    // list

    public function test_list_received_documents()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->list($type);

        $this->assertInstanceOf(ReceivedDocumentList::class, $response);
        $this->assertInstanceOf(ReceivedDocumentPagination::class, $response->getPagination());
        $this->assertIsArray($response->getItems());
        $this->assertCount(2, $response->getItems());
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response->getItems()[0]);
    }

    public function test_all_documents()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeAll()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->all($type);

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response[0]);
    }

    public function test_error_on_all_documents()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeError(),
                401
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->all($type);

        $this->assertInstanceOf(Error::class, $response);
    }

    public function test_list_received_documents_has_received_documents_method()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->list($type);

        $this->assertTrue($response->hasItems());
    }

    public function test_empty_list_received_documents_has_received_documents_method()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getEmptyReceivedDocumentsFakeList()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->list($type);

        $this->assertFalse($response->hasItems());
    }

    public function test_error_on_list_received_documents()
    {
        $type = 'expense';

        Http::fake([
            'received_documents?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeError(),
                401
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->list($type);

        $this->assertInstanceOf(Error::class, $response);
    }

    // pagination

    public function test_query_parameters_parsing()
    {
        $received_document_pagination = new ReceivedDocumentPagination((object) []);

        $query_params = $received_document_pagination->getParsedQueryParams('https://fake_url.com/entity?first=Lorem&type=document_type&second=Ipsum');

        $this->assertIsObject($query_params);

        $this->assertObjectHasProperty('type', $query_params);
        $this->assertObjectHasProperty('additional_data', $query_params);

        $this->assertEquals('document_type', $query_params->type);
        $this->assertIsArray($query_params->additional_data);
        $this->assertCount(2, $query_params->additional_data);
    }

    public function test_go_to_received_document_next_page()
    {
        $type = 'expense';

        $received_document_list = new ReceivedDocumentList(json_decode(
            (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList([
                'next_page_url' => 'https://fake_url/received_documents?type='.$type.'&per_page=10&page=2',
            ])
        ));

        Http::fake([
            'received_documents?per_page=10&page=2&type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $next_page_response = $received_document_list->getPagination()->goToNextPage();

        $this->assertInstanceOf(ReceivedDocumentList::class, $next_page_response);
    }

    public function test_go_to_received_document_prev_page()
    {
        $type = 'expense';

        $received_document_list = new ReceivedDocumentList(json_decode(
            (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList([
                'prev_page_url' => 'https://fake_url/received_documents?type='.$type.'&per_page=10&page=1',
            ])
        ));

        Http::fake([
            'received_documents?per_page=10&page=1&type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $prev_page_response = $received_document_list->getPagination()->goToPrevPage();

        $this->assertInstanceOf(ReceivedDocumentList::class, $prev_page_response);
    }

    public function test_go_to_received_document_first_page()
    {
        $type = 'expense';

        $received_document_list = new ReceivedDocumentList(json_decode(
            (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList([
                'first_page_url' => 'https://fake_url/received_documents?type='.$type.'&per_page=10&page=1',
            ])
        ));

        Http::fake([
            'received_documents?per_page=10&page=1&type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $first_page_response = $received_document_list->getPagination()->goToFirstPage();

        $this->assertInstanceOf(ReceivedDocumentList::class, $first_page_response);
    }

    public function test_go_to_received_document_last_page()
    {
        $type = 'expense';

        $received_document_list = new ReceivedDocumentList(json_decode(
            (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList([
                'last_page_url' => 'https://fake_url/received_documents?type='.$type.'&per_page=10&page=2',
            ])
        ));

        Http::fake([
            'received_documents?per_page=10&page=2&type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentsFakeList()
            ),
        ]);

        $last_page_response = $received_document_list->getPagination()->goToLastPage();

        $this->assertInstanceOf(ReceivedDocumentList::class, $last_page_response);
    }

    // single

    public function test_detail_received_document()
    {
        $document_id = 1;

        Http::fake([
            'received_documents/'.$document_id => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->detail($document_id);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    public function test_bin_received_document()
    {
        $document_id = 1;

        Http::fake([
            'bin/received_documents/'.$document_id => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->bin($document_id);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    public function test_delete_received_document()
    {
        $document_id = 1;

        Http::fake([
            'received_documents/'.$document_id => Http::response(),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->delete($document_id);

        $this->assertEquals('Document deleted', $response);
    }

    public function test_received_document_bin_detail_from_detail()
    {
        $document_id = 1;

        Http::fake([
            'received_documents/'.$document_id => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail()
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->binDetail($document_id);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    public function test_received_document_bin_detail_from_bin()
    {
        $document_id = 1;

        Http::fake([
            'received_documents/'.$document_id => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail()
            ),
        ]);

        Http::fake([
            'received_documents/'.$document_id.'?fields=id' => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeErrorDetail(),
                401
            ),
        ]);

        $received_documents = new ReceivedDocument();
        $response = $received_documents->binDetail($document_id, [
            'fields' => 'id',
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    // create

    public function test_create_received_document()
    {
        $entity_name = 'Test S.R.L';

        Http::fake([
            'received_documents' => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail([
                    'entity' => [
                        'name' => $entity_name,
                    ],
                ])
            ),
        ]);

        $received_document = new ReceivedDocument();
        $response = $received_document->create([
            'data' => [
                'type' => 'expense',
                'entity' => [
                    'name' => $entity_name,
                ],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    public function test_validation_error_on_create_received_document()
    {
        $received_document = new ReceivedDocument();
        $response = $received_document->create([]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->create([
            'data' => [],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());
        $this->assertArrayHasKey('data.type', $response->messages());
        $this->assertArrayHasKey('data.entity.name', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->create([
            'data' => [
                'type' => 'fake_type',
                'entity' => [
                    'name' => 'Test S.R.L.',
                ],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.type', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->create([
            'data' => [
                'type' => 'expense',
                'entity' => [],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.entity.name', $response->messages());
    }

    // edit

    public function test_edit_received_document()
    {
        $document_id = 1;
        $entity_name = 'Test S.R.L Updated';

        Http::fake([
            'received_documents/'.$document_id => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeDetail([
                    'entity' => [
                        'name' => $entity_name,
                    ],
                ])
            ),
        ]);

        $received_document = new ReceivedDocument();
        $response = $received_document->edit($document_id, [
            'data' => [
                'entity' => [
                    'name' => $entity_name,
                ],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentEntity::class, $response);
    }

    public function test_validation_error_on_edit_received_document()
    {
        $document_id = 1;

        $received_document = new ReceivedDocument();
        $response = $received_document->edit($document_id, []);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->edit($document_id, [
            'data' => [],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());
        $this->assertArrayHasKey('data.entity.name', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->edit($document_id, [
            'data' => [
                'entity' => [],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.entity.name', $response->messages());
    }

    // new totals

    public function test_get_new_totals_received_document()
    {
        Http::fake([
            'received_documents/totals' => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeTotals()
            ),
        ]);

        $received_document = new ReceivedDocument();
        $response = $received_document->getNewTotals([
            'data' => [
                'type' => 'expense',
                'entity' => [
                    'name' => 'Test S.P.A',
                ],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentGetExistingTotals::class, $response);
    }

    public function test_validation_error_on_get_new_totals_received_document()
    {
        $received_document = new ReceivedDocument();
        $response = $received_document->getNewTotals([]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->getNewTotals([
            'data' => [],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());
        $this->assertArrayHasKey('data.type', $response->messages());
        $this->assertArrayHasKey('data.entity.name', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->getNewTotals([
            'data' => [
                'type' => 'fake_type',
                'entity' => [
                    'name' => 'Test S.P.A.',
                ],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.type', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->getNewTotals([
            'data' => [
                'type' => 'expense',
                'entity' => [],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.entity.name', $response->messages());
    }

    // existing totals

    public function test_get_existing_totals_received_document()
    {
        $document_id = 1;

        Http::fake([
            'received_documents/'.$document_id.'/totals' => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakeTotals()
            ),
        ]);

        $received_document = new ReceivedDocument();
        $response = $received_document->getExistingTotals($document_id, [
            'data' => [
                'entity' => [
                    'name' => 'Test S.R.L',
                ],
            ],
        ]);

        $this-> assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentGetExistingTotals::class, $response);
    }

    public function test_validation_error_on_get_existing_totals_received_document()
    {
        $document_id = 1;

        $received_document = new ReceivedDocument();
        $response = $received_document->getExistingTotals($document_id, []);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->getExistingTotals($document_id, [
            'data' => [],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data', $response->messages());
        $this->assertArrayHasKey('data.entity.name', $response->messages());

        $received_document = new ReceivedDocument();
        $response = $received_document->getExistingTotals($document_id, [
            'data' => [
                'entity' => [],
            ],
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(MessageBag::class, $response);
        $this->assertArrayHasKey('data.entity.name', $response->messages());
    }

    // info

    public function test_pre_create_info_received_document()
    {
        $type = 'expense';

        Http::fake([
            'received_documents/info?type='.$type => Http::response(
                (new ReceivedDocumentFakeResponse())->getReceivedDocumentFakePreCreateInfo()
            ),
        ]);

        $received_document = new ReceivedDocument();
        $response = $received_document->preCreateInfo($type);

        $this->assertNotNull($response);
        $this->assertInstanceOf(ReceivedDocumentPreCreateInfo::class, $response);
    }
}
