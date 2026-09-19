<?php

interface ModuleInterface
{
    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param Datagrab_model $DG
     * @param array          $data
     * @return array
     */
    public function displayConfiguration(Datagrab_model $DG, array $data = []): array;

    /**
     * @param Datagrab_model $DG
     * @return array
     */
    public function saveConfiguration(Datagrab_model $DG): array;

    /**
     * @param Datagrab_model $DG
     * @param array          $data
     * @param array          $item
     * @param array          $custom_fields
     * @param string         $action
     * @return mixed
     */
    public function handle(Datagrab_model $DG, array &$data = [], array $item = [], array $custom_fields = [], string $action = '');
}
