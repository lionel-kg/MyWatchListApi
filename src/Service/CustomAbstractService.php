<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Firebase\JWT\JWT;


Abstract class CustomAbstractService
{
    private $params;
    private $em;
    private $serializer;
    private $slugger;

    public function __construct(
        EntityManagerInterface  $em,
        ParameterBagInterface $params,
        SerializerInterface $serializer,
        SluggerInterface $slugger
    )
    {
        $this->em = $em;
        $this->params = $params;
        $this->serializer = $serializer;
        $this->slugger = $slugger;
    }

    /**
     * @param string $jwt
     * @return UserEntity|null
     * @throws \JsonException
     */
    public function checktJwt(string $jwt):?UserEntity
    {
        $entityClassName = "";
        $jwtDecode = (array) json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $jwt)[1]))));
        [
            "username" => $email,
            //"token" => $token
        ] =  $jwtDecode;
        if($jwtDecode["roles"][0] === "ROLE_CLIENT") {
            $entityClassName = ClientEntity::class;
        } elseif ($jwtDecode["roles"][0] === "ROLE_ADMIN") {
            $entityClassName = AdminEntity::class;
        }
        $user = $this->em->getRepository($entityClassName)->findOneBy(["email"=>$email]);
        return $user;
    }

    /**
     * @param array $entities
     * @param array $groups
     * @return array
     * @throws \JsonException
     */
    public function getInfoSerialize(array $entities,array $groups): array
    {
        $array = [];
        foreach ($entities as $entity){
            $data = $this->serializer->serialize($entity,"json",["groups"=>$groups]);
            $array[] = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }
        return $array;
    }
    /**
     * @param string $entityClassName
     * @param array $fields
     * @param array $parameters
     * @return object
     */
    public function createSimpleEntity(
        string $entityClassName,
        array $fields,
        array $parameters
    ): object
    {
        $entity = new $entityClassName();
        foreach ($fields as $field) {
            if (isset($parameters[$field])) {
                $fieldValue = $parameters[$field];
                if ($fieldValue !== null) {
                    $setMethodName = "set".ucfirst($field);
                    $entity->$setMethodName($fieldValue);
                }
            }
        }
        return $entity;
    }

    /**
     * @param object $entity
     * @param array $fields
     * @param array $parameters
     * @return object
     */
    public function editSimpleEntity(
        object $entity,
        array $fields,
        array $parameters
    ): object
    {
        foreach ($fields as $field) {
            if (isset($parameters[$field])){
                $fieldValue = $parameters[$field];
                $setMethodName = "set".ucfirst($field);
                $entity->$setMethodName($fieldValue);
            }
        }
        return $entity;
    }
}