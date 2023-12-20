<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JetBrains\PhpStorm\ArrayShape;
use JMS\Serializer\Annotation as Serializer;
use JsonSerializable;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Hateoas\Relation(
 *     name="parent",
 *     href="expr(constant('\\App\\Controller\\ApiUsersQueryController::RUTA_API'))"
 * )
 *
 * @Hateoas\Relation(
 *     name="self",
 *     href="expr(constant('\\App\\Controller\\ApiUsersQueryController::RUTA_API') ~ '/' ~ object.getId())"
 * )
 */
#[ORM\Entity, ORM\Table(name: "users")]
#[ORM\UniqueConstraint(name: "IDX_UNIQ_EMAIL", columns: [ "email" ])]
#[Serializer\XmlNamespace(uri: "http://www.w3.org/2005/Atom", prefix: "atom")]
#[Serializer\AccessorOrder(order: 'custom', custom: [ "id", "email", "roles", "_links" ]) ]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JsonSerializable, JWTUserInterface
{
    public final const USER_ATTR = 'user';
    public final const EMAIL_ATTR = 'email';
    public final const PASSWD_ATTR = 'password';
    public final const ROLES_ATTR = 'roles';

    #[ORM\Column(
        name: "id",
        type: "integer",
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: "IDENTITY")]
    #[Serializer\XmlAttribute]
    protected ?int $id = 0;

    #[ORM\Column(
        name: "email",
        type: "string",
        length: 180,
        unique: true,
        nullable: false
    )]
    #[Serializer\SerializedName(User::EMAIL_ATTR), Serializer\XmlElement(cdata: false)]
    protected string $email;

    #[ORM\Column(
        name: "roles",
        type: "json"
    )]
    #[Serializer\SerializedName(User::ROLES_ATTR), Serializer\XmlElement(cdata: false)]
    #[Serializer\Accessor(getter: "getRoles"), Serializer\XmlList(entry: "role")]
    protected array $roles;

    /**
     * Hashed password
     */
    #[ORM\Column(
        name: "password",
        type: "string",
        length: 60,
        nullable: false
    )]
    #[Serializer\Exclude]
    protected string $password;

    /**
     * User constructor.
     * @param string $email
     * @param string $password
     * @param array<string> $roles
     */
    public function __construct(string $email = '', string $password = '', array $roles = [ 'ROLE_USER' ])
    {
        $this->email = $email;
        $this->roles = $roles;
        $this->setPassword($password);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->password = '';
    }

    /**
     * @inheritDoc
     *
     * @return array<string,array<string>|int|string|null>
     */
    #[ArrayShape([
        'Id' => "int|null",
        self::EMAIL_ATTR => "string",
        self::ROLES_ATTR => "array|string[]"
    ])]
    public function jsonSerialize(): array
    {
        return [
            'Id' => $this->getId(),
            self::EMAIL_ATTR => $this->getEmail(),
            self::ROLES_ATTR => $this->getRoles(),
        ];
    }

    /**
     * @inheritDoc
     *
     * @param string $username
     * @param array<string,mixed> $payload
     */
    public static function createFromPayload($username, array $payload): User|JWTUserInterface
    {
        $user = new self(
            $username,
            '',
            $payload['roles'], // Added by default
            // $payload['email'],  // Custom
        );
        $user->id = intval($payload['id']);
        return $user;
    }
}
