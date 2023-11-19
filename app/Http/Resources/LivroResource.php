<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LivroResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Inicializa uma coleção plana de todos os índices e seus pais
        $allIndices = $this->indices->map(function ($indice) {
            return [
                'id' => $indice->id,
                'titulo' => $indice->titulo,
                'pagina' => $indice->pagina,
                'indice_pai_id' => $indice->indice_pai_id,
            ];
        });

        // Constrói a árvore de índices a partir da coleção
        $indicesHierarchy = IndiceResource::buildHierarchy($allIndices->toArray());

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'usuario_publicador' => ['id' => $this->usuarioPublicador->id, 'nome' => $this->usuarioPublicador->name],
            'indices' => $indicesHierarchy, // Retorna a árvore de índices
        ];
    }
}
