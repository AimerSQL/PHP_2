<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JsonSerializable;
use Symfony\Config\JmsSerializer\Handlers\DatetimeConfig;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
#[ORM\Table(name: "results")]
#[Serializer\XmlNamespace(uri: "http://www.w3.org/2005/Atom", prefix: "atom")]
#[Serializer\AccessorOrder(order: 'custom', custom: [ "id", "result", "user", "time","_links" ]) ]
class Result implements JsonSerializable
{
    public final const RESULT_ATTR = "result";
    public final const TIME_ATTR = "time";
    public final const USER_ATTR = "user";
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name:"id",
        type: "integer",
        nullable: false
    )]
    private ?int $id = null;

    #[ORM\Column(
        name:"result",
        type: "integer",
        nullable: false
    )]
    private ?int $result = null;

    #[ORM\Column(
        name:"time",
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private ?\DateTimeInterface $time = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: "user_id",
        referencedColumnName: "id",
        onDelete: "CASCADE"
    )]
    private ?User $user = null;

    public function __construct(int $result=0, ?User $user=null, ?\Datetime $time=null)
    {
        $this->result = $result;
        $this->id = 0;
        $this->user = $user;
        $this->time = $time;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getResult(): ?int
    {
        return $this->result;
    }

    public function setResult(int $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }
    public function setTimeFromString(string $time):static{
        $this->time = \DateTime::createFromFormat('Y-m-d H:i:s',$time);
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'user' => [
                'Id' => $this->id,
                'result' => $this->result,
                'time' => $this->time->format('Y-m-d H:i:s'),
                'user' => $this->user
            ]
        ];
    }

}