<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Provides a full tree of paths that are different from one environment to another for a project
 * @author $Author$
 * @class Diff
 */
class Diff extends Generic
{
    /**
     * 'all' means scan all tree, 'diff' means only diff
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $node='';

	/**
	 * @var \Pub\Data_Objects\Models\Environment
	 */
	protected $targetEnvironment;

	/**
	 * @var \stdClass
	 */
	protected $treeRoot = null;

    /**
     * @var array of \Pub\Data_Objects\Models\LogEntryPath
     */
    protected $files;

	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();

		$this->determineParametersPipeline
			->determineProjectId()
			->determineEnvironmentId()
            ->determineNode()
            ->determineMode();
	}

    protected function determineMode()
    {
        $this->mode = $this->getPostParameterValue('mode','diff');
    }

    /**
     * get the node (path) parameter (required)
     */
    protected function determineNode()
    {
        $node = $this->getPostParameterValue('node','');
        if(strtolower($node) != 'root')
        {
            $this->node = $node;
        }
    }

	/**
	 * get the projectId parameter (required)
	 */
	protected function determineProjectId()
	{
		$this->projectId = $this->getPostParameterValue('projectId');
		if(empty($this->projectId)) $this->requestFailed('projectId not found');
	}

	/**
	 * get the environmentId parameter (required);
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->getPostParameterValue('environmentId');
		if(!($this->environmentId > -1))
		{
			 $this->requestFailed('environmentId was not found');
		}
	}

	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();

		$this->fetchInformationPipeline
			->fetchTargetEnvironment();

        if ($this->mode == 'all') {
            $this->fetchInformationPipeline->fetchFiles();
        } else {
            $this->fetchInformationPipeline->fetchDiff();
        }
	}

	/**
	 * fetch the targetEnvironment by environmentId+1
	 */
	protected function fetchTargetEnvironment()
	{
		if(empty($this->project->environments[$this->environmentId+1]))
		{
			$this->requestFailed('environmentId: '.($this->environmentId+1).' not found for projectId: '.$this->projectId);
		}
		$this->targetEnvironment = $this->project->environments[$this->environmentId+1];
	}

    /**
     * Fetch 'svn diff' or 'svn ls' files list
     */
    protected function fetchFiles()
    {
        $files = $this->sourceControlClient->ls($this->project->location . $this->environment->path . $this->node);

        /** @var \Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN\FileListEntry $file */
        foreach($files as $file) {
            $LogFileEntry = new \Pub\Data_Objects\Models\LogEntryPath();
            $LogFileEntry->action = 'M';
            $LogFileEntry->actionName = 'Modified';

            if ($file->isDirectory) {
                $LogFileEntry->fileType = 'dir';
            } else {
                $LogFileEntry->fileType = 'file';
            }

            $LogFileEntry->path = str_replace(
                $this->project->location . $this->environment->path . $this->node,
                '',
                $file->fullPath
            );

            $this->files[] = $LogFileEntry;
        }
    }

	/**
	 * fetch the diff information on the two environments and build the tree
	 */
	protected function fetchDiff()
	{
		$this->files = $this->sourceControlClient->diff(
            $this->project->location . $this->environment->path,
            $this->project->location . $this->targetEnvironment->path
        );
	}


    protected function setProcessInformationPipeline()
    {
        parent::setProcessInformationPipeline();

        if ($this->mode == 'diff') {
            $this->processInformationPipeline->processFilesList();
        } else {
            $this->processInformationPipeline->processAllFilesList();
        }

    }

    protected function processAllFilesList()
    {
        /** @var \Pub\Data_Objects\Models\LogEntryPath $file */
        if (empty($this->files)) return;

        foreach ($this->files as $file) {
            $node = $this->createTreeNode(trim($file->path,'/'));
            unset($node->files);
            $node->leaf = $file->fileType == 'file' ? true : false;

            $node->id = ($this->node == '/' ? '' : $this->node)
                . $file->path;

            $node->qtip = $node->id;

            $this->output->files[] = $node;
        }
    }

    protected function processFilesList()
    {
        $this->treeRoot = $this->createTreeNode('ROOT');
        $this->treeRoot->leaf=false;
        $this->treeRoot->id='/';
        $this->treeRoot->qtip = '/';

        if (!empty($this->files)) {
            foreach($this->files as $file)
            {
                if(!empty($file->path) && $file->action != 'D')
                {
                    $this->addToTree($file);
                }
            }
        }

        $this->output->files = $this->treeRoot;
    }

	/**
	 * The beginning function of the recursive tree builder
	 * @param \Pub\Data_Objects\Models\LogEntryPath $file
	 */
	protected function addToTree($file)
	{
		$pieces = explode('/',substr($file->path,1));

		$this->findParentNode(0,$pieces,$this->treeRoot,$file);
	}

	/**
	 * Recursive function for building a tree
	 * @param string $path
	 * @param integer $position
	 * @param string[] $pieces
	 * @return \stdClass
	 */
	protected function createTreeNode($path, $position=0, $pieces=null, $file = null)
	{
		$node = (object)array('text'=>$path,'files'=>array(), 'leaf'=>false);

		if(empty($pieces)) return $node;

		//add in the full path information up to this point for the node to help with the tree view
		$node->qtip = '/'.implode('/',array_slice($pieces, 0, $position+1));

		return $node;
	}

	/**
	 * Recursive function for building a file tree
	 * @param integer $position
	 * @param string[] $pieces
	 * @param \stdClass $parentNode
	 */
	protected function findParentNode($position,$pieces,$parentNode,$file = null)
	{
		if(count($pieces) == $position) return;

		//root check
		foreach($parentNode->files as &$node)
		{
			if($node->text == $pieces[$position])
			{
				$node->leaf = false;
				if(count($pieces) > $position+1)
				{
					//need to go deeper
					$this->findParentNode($position+1, $pieces, $node, $file);
					return;
				}
				//change the node leaf value and we're done
				$node->files[] = $this->createTreeNode($pieces[$position],$position,$pieces, $file);
				return;
			}
		}

		//no child path found we will create one
		$newNode = $this->createTreeNode($pieces[$position],$position,$pieces, $file);
        $parentNode->files[] = $newNode;

        if(count($pieces) > $position+1)
		{
			//need to go deeper
			$this->findParentNode($position+1, $pieces, $newNode);
			return;
		} elseif ($file instanceof \Pub\Data_Objects\Models\LogEntryPath && $file->fileType == 'file') {
            $parentNode->files[count($parentNode->files)-1]->leaf = true;
        }
	}
}