<?php

namespace GetCandy\Api\Core\Search\Drivers\Elasticsearch\Actions;

use Elastica\Bulk;
use Elastica\Client;
use Elastica\Document;
use Elastica\Mapping;
use GetCandy\Api\Core\Addresses\Models\Address;
use GetCandy\Api\Core\Customers\Actions\FetchCustomerGroups;
use GetCandy\Api\Core\Languages\Actions\FetchLanguages;
use GetCandy\Api\Core\Search\Actions\GetIndiceNamesAction;
use GetCandy\Api\Core\Search\Drivers\Elasticsearch\Actions\FetchIndex;
use GetCandy\Api\Core\Search\Drivers\Elasticsearch\Actions\FetchProductMapping;
use GetCandy\Api\Core\Search\Drivers\Elasticsearch\Events\IndexingCompleteEvent;
use GetCandy\Api\Core\Search\Indexables\ProductIndexable;
use Lorisleiva\Actions\Action;

class IndexProducts extends Action
{
    /**
     * Determine if the user is authorized to make this action.
     *
     * @return bool
     */
    public function authorize()
    {
        if (app()->runningInConsole()) {
            return true;
        }
        return $this->user()->can('index-documents');
    }

    /**
     * Get the validation rules that apply to the action.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'products' => 'required',
            'uuid' => 'required',
            'final' => 'boolean',
        ];
    }

    /**
     * Execute the action and return a result.
     *
     * @return \GetCandy\Api\Core\Addresses\Models\Address
     */
    public function handle(Client $client)
    {
        $languages = FetchLanguages::run([
            'paginate' => false,
        ])->pluck('lang');

        $customerGroups = FetchCustomerGroups::run([
            'paginate' => false
        ]);


        $indexes = FetchIndex::run([
            'languages' => $languages->toArray(),
            'type' => 'products',
            'uuid' => $this->uuid,
        ]);


        $documents = [];

        foreach ($this->products as $product) {
            $indexables = FetchProductDocument::run([
                'model' => $product,
                'customer_groups' => $customerGroups
            ]);

            foreach ($indexables as $document) {
                $documents[$document->lang][] = $document;
            }
        }

        foreach ($indexes as $index) {
            // If the index doesn't exist, then we update the mapping
            if (!$index->actual->exists()) {
                $mapping = new Mapping();
                $mapping->setProperties(
                    FetchProductMapping::run()
                );
                $mapping->send($index->actual);
            }

            // Get the documents for the index language.
            $docs = collect($documents[$index->language] ?? [])->map(function ($document) {
                return new Document($document->getId(), $document->getData());
            });

            $bulk = new Bulk($client);
            $bulk->setIndex($index->actual);
            $bulk->addDocuments($docs->toArray());
            $bulk->send();
        }

        if ($this->final) {
            event(new IndexingCompleteEvent($indexes, 'products'));
        }

        // dd($index);
        // $this->timestamp = microtime(true);

        // dd($this->documents);

        // $aliases = $this->getNewAliases(new ProductIndexable, 'products');

        // $indiceNames = GetIndiceNamesAction::run([
        //     'filter' => $this->getNewIndexName()
        // ]);

        // foreach ($this->products as $product) {
        //     $documents = (new ProductIndexable($product))
        //         ->setIndexName($this->getNewIndexName())
        //         ->setSuffix($this->timestamp)
        //         ->getDocuments();
        //     dd($documents);
        // }
        // dd($this->products);
    }
}
