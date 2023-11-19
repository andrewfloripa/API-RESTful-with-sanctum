<?php 

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class IndiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // Monta a estrutura básica do índice
        $indiceArray = [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'pagina' => $this->pagina,    
        ];

        // Se este índice tem um 'indice_pai_id', adiciona o pai.
        // Caso contrário, adiciona os subíndices.
        if ($this->indice_pai_id) {
            $indiceArray['pai'] = new IndiceResource($this->whenLoaded('indicePai'));
        } else {
            $indiceArray['subindices'] = IndiceResource::collection($this->whenLoaded('subindices'));
        }

        return $indiceArray;
    }

    /**
     * Cria uma árvore de índices e seus subíndices.
     *
     * @param array $indicesArray
     * @param int|null $parentId
     * @return array
     */
    public static function buildHierarchy($indicesArray, $parentId = null): array
    {
        $tree = [];
        foreach ($indicesArray as $indice) {
            if ($indice['indice_pai_id'] == $parentId) {
                // Exclui 'indice_pai_id' da saída
                unset($indice['indice_pai_id']);
                $children = self::buildHierarchy($indicesArray, $indice['id']);
                if ($children) {
                    $indice['subindices'] = $children;
                }else{
                    $indice['subindices'] = [];
                }

                $tree[] = $indice;
            }
        }
        return $tree;
    }
}
